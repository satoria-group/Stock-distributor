<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Models\StockApiLog;
use App\Services\StockImportService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * API push stok harian: sistem distributor mengirim snapshot stok, kita
 * memprosesnya dengan jalur impor yang sama seperti berkas Excel.
 */
class StockPushController extends Controller
{
    /** Batas item per kiriman; kiriman lebih besar dipecah oleh pengirim. */
    public const MAX_ITEMS = 10000;

    /** Status hasil impor => kode HTTP. */
    private const HTTP_STATUS = [
        'success' => 200,
        'partial_unmapped' => 200,
        'forbidden_distributor' => 403,
        'unknown_distributor' => 422,
        'inactive_distributor' => 422,
        'invalid_batch_data' => 422,
        'all_unmapped' => 422,
        'invalid_template' => 422,
    ];

    public function store(Request $request, StockImportService $service): JsonResponse
    {
        /** @var ApiClient $client */
        $client = $request->attributes->get('api_client');

        $validator = Validator::make($request->all(), [
            'request_id' => ['required', 'string', 'max:100'],
            'stock_date' => ['required', 'date_format:Y-m-d'],
            'branches' => ['required', 'array', 'min:1', 'max:500'],
            'branches.*.distributor_code' => ['required', 'string', 'max:100', 'distinct'],
            'branches.*.items' => ['required', 'array', 'min:1'],
            'branches.*.items.*.item_name' => ['required', 'string', 'max:255'],
            'branches.*.items.*.qty' => ['required', 'numeric', 'min:0'],
            'branches.*.items.*.unit' => ['nullable', 'string', 'max:50'],
            'branches.*.items.*.batch_no' => ['required', 'string', 'max:100'],
            'branches.*.items.*.expired_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $validator->after(function ($v) use ($request) {
            $total = collect($request->input('branches', []))->sum(fn ($b) => is_array($b['items'] ?? null) ? count($b['items']) : 0);
            if ($total > self::MAX_ITEMS) {
                $v->errors()->add('branches', 'Maksimal '.self::MAX_ITEMS." item per kiriman ({$total} dikirim). Pecah menjadi beberapa kiriman.");
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status' => 'validation_error',
                'message' => 'Data kiriman tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Log dibuat LEBIH DULU: kunci unik (klien, request_id) yang menolak
        // kiriman ganda — termasuk dua kiriman yang datang bersamaan.
        // Dibungkus transaksi supaya pelanggaran kunci di PostgreSQL hanya
        // membatalkan savepoint-nya, bukan transaksi pemanggil.
        try {
            $log = DB::transaction(fn () => StockApiLog::create([
                'api_client_id' => $client->id,
                'request_id' => $data['request_id'],
                'ip_address' => $request->ip(),
                'tanggal_snapshot' => $data['stock_date'],
                'status' => 'processing',
                'branch_count' => count($data['branches']),
            ]));
        } catch (UniqueConstraintViolationException) {
            $previous = $client->logs()->where('request_id', $data['request_id'])->first();

            return response()->json([
                'status' => 'duplicate_request',
                'message' => "request_id '{$data['request_id']}' sudah pernah dikirim. Gunakan request_id baru untuk kiriman baru.",
                'previous' => $previous ? $this->logPayload($previous) : null,
            ], 409);
        }

        $branches = [];
        foreach ($data['branches'] as $branch) {
            $branches[$branch['distributor_code']] = array_map(fn ($item) => [
                'item_name' => trim($item['item_name']),
                'qty' => (float) $item['qty'],
                'satuan' => isset($item['unit']) && trim($item['unit']) !== '' ? trim($item['unit']) : null,
                'ed' => $item['expired_date'] ?? null,
                'batch' => trim($item['batch_no']),
            ], $branch['items']);
        }

        try {
            $result = $service->importFromApi($client->group, $data['stock_date'], $branches);
        } catch (\Throwable $e) {
            report($e);
            $log->update(['status' => 'failed', 'error_message' => 'Kesalahan server: '.$e->getMessage()]);

            return response()->json([
                'status' => 'failed',
                'message' => 'Terjadi kesalahan di server. Tidak ada data yang disimpan; silakan kirim ulang dengan request_id baru.',
                'request_id' => $data['request_id'],
            ], 500);
        }

        $log->update([
            'status' => $result['status'],
            'total_rows' => $result['total_rows'],
            'imported_rows' => $result['imported_rows'],
            'skipped_rows' => $result['skipped_rows'],
            'error_message' => $result['error'],
            'details' => $result['details'] + [
                'unmapped_items' => array_values(array_unique(array_column($result['skipped_items'], 'item_name'))),
            ],
        ]);

        return response()->json($this->logPayload($log->fresh()), self::HTTP_STATUS[$result['status']] ?? 422);
    }

    public function show(Request $request, string $requestId): JsonResponse
    {
        /** @var ApiClient $client */
        $client = $request->attributes->get('api_client');
        $log = $client->logs()->where('request_id', $requestId)->first();

        if (! $log) {
            return response()->json([
                'status' => 'not_found',
                'message' => "request_id '{$requestId}' tidak ditemukan.",
            ], 404);
        }

        return response()->json($this->logPayload($log));
    }

    /** Bentuk respons untuk satu kiriman — sama untuk POST dan GET. */
    private function logPayload(StockApiLog $log): array
    {
        $details = $log->details ?? [];

        return [
            'status' => $log->status,
            'message' => $log->error_message ?? ($log->status === 'success' ? 'Data stok berhasil disimpan.' : null),
            'request_id' => $log->request_id,
            'stock_date' => $log->tanggal_snapshot?->format('Y-m-d'),
            'total_rows' => $log->total_rows,
            'imported_rows' => $log->imported_rows,
            'skipped_rows' => $log->skipped_rows,
            'unmapped_items' => $details['unmapped_items'] ?? [],
            'received_at' => $log->created_at?->toIso8601String(),
        ];
    }
}
