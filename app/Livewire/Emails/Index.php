<?php

namespace App\Livewire\Emails;

use App\Models\DistributorItem;
use App\Models\StockEmailLog;
use App\Models\User;
use App\Services\HtmlSanitizerService;
use App\Services\ImapService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Index extends Component
{
    public int $page = 1;

    public int $perPage = 15;

    public string $search = '';

    public bool $onlyWithAttachments = false;

    public bool $onlyDailyStock = false;

    public ?string $selectedUid = null;

    public ?array $selectedEmail = null;

    public bool $showLogsModal = false;

    public function openLogsModal(): void
    {
        $this->showLogsModal = true;
    }

    public function closeLogsModal(): void
    {
        $this->showLogsModal = false;
    }

    public function runAutomationNow(ImapService $imapService): void
    {
        if (! $imapService->isConfigured()) {
            session()->flash('error', 'Koneksi mail server belum dikonfigurasi pada file .env.');

            return;
        }

        try {
            \Illuminate\Support\Facades\Artisan::call('stock:process-emails', ['--limit' => 10]);
            $imapService->clearCache();
            $this->page = 1;
            session()->flash('status', 'Otomasi pembacaan email berhasil dijalankan! Data lampiran yang valid telah diproses.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Gagal menjalankan otomasi: '.$e->getMessage());
        }
    }

    /**
     * Memastikan seluruh item unmapped dari log email terdaftar / ter-restore kembali
     * ke antrean DistributorItem, lalu mengarahkan ke halaman Mapping Item Distributor.
     */
    public function openMappingForLog(int $logId)
    {
        $log = StockEmailLog::find($logId);
        if ($log && ! empty($log->details['unique_skipped_names']) && $log->distributor_id) {
            foreach ($log->details['unique_skipped_names'] as $itemName) {
                $rawName = trim($itemName);
                $norm = mb_strtolower(trim(preg_replace('/\s+/', ' ', $rawName)));
                $existing = DistributorItem::withTrashed()
                    ->where('distributor_id', $log->distributor_id)
                    ->whereRaw('LOWER(TRIM(item_name)) = ?', [$norm])
                    ->first();

                if ($existing) {
                    if ($existing->trashed()) {
                        $existing->restore();
                    }
                } else {
                    try {
                        DistributorItem::create([
                            'distributor_id' => $log->distributor_id,
                            'item_name' => $rawName,
                            'satuan' => 'PCS',
                            'netsuite_item_id' => null,
                        ]);
                    } catch (\Throwable) {
                        // Abaikan race condition
                    }
                }
            }
        }

        return redirect()->route('distributor-items.index', [
            'distributorFilter' => $log?->distributor_id,
            'mappingFilter' => 'unmapped',
        ]);
    }

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user) {
            abort(401);
        }

        $isAuthorized = $user->hasRole(User::ROLE_ADMIN)
            || $user->hasRole(User::ROLE_LOGISTIK)
            || $user->can('stock.upload')
            || $user->can('stock.view');

        if (! $isAuthorized) {
            abort(403, 'Anda tidak memiliki akses ke Inbox Email Distributor.');
        }
    }

    public function selectEmail(string $uid, ImapService $imapService, HtmlSanitizerService $sanitizer): void
    {
        $this->selectedUid = $uid;

        // Ambil detail sekaligus tandai sudah dibaca dalam SATU sesi IMAP.
        // Sebelumnya getMessage() + markAsRead() membuka dua koneksi terpisah.
        $detail = $imapService->getMessage($uid, markRead: true);

        if ($detail) {
            $detail['is_read'] = true;

            // Sanitize HTML body to prevent XSS
            if (! empty($detail['html_body'])) {
                $detail['sanitized_html'] = $sanitizer->sanitize($detail['html_body']);
            } else {
                $detail['sanitized_html'] = null;
            }
            $this->selectedEmail = $detail;
        } else {
            $this->selectedEmail = null;
            session()->flash('error', 'Gagal memuat detail email atau email tidak ditemukan di server.');
        }

    }

    public function toggleReadStatus(string $uid, ImapService $imapService): void
    {
        if ($this->selectedEmail && (string) $this->selectedEmail['uid'] === (string) $uid) {
            if ($this->selectedEmail['is_read']) {
                $imapService->markAsUnread($uid);
                $this->selectedEmail['is_read'] = false;
            } else {
                $imapService->markAsRead($uid);
                $this->selectedEmail['is_read'] = true;
            }
        }
    }

    public function closeEmail(): void
    {
        $this->selectedUid = null;
        $this->selectedEmail = null;
    }

    public function refreshInbox(ImapService $imapService): void
    {
        $imapService->clearCache();
        $this->page = 1;
        $this->closeEmail();
        session()->flash('status', 'Kotak masuk berhasil diperbarui langsung dari mail server.');
    }

    public function gotoPage(int $page): void
    {
        $this->page = max(1, $page);
        $this->closeEmail();
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function updatedOnlyWithAttachments(): void
    {
        $this->page = 1;
    }

    public function updatedOnlyDailyStock(): void
    {
        $this->page = 1;
    }

    public function render(ImapService $imapService)
    {
        $isConfigured = $imapService->isConfigured();
        $inboxResult = $imapService->getInbox($this->page, $this->perPage);

        $emails = collect($inboxResult['data'] ?? []);

        // Filter subject pattern "Satoria Daily Stock"
        if ($this->onlyDailyStock) {
            $emails = $emails->where('is_daily_stock', true);
        }

        // Filter emails with attachments
        if ($this->onlyWithAttachments) {
            $emails = $emails->where('has_attachments', true);
        }

        // Client-side quick filter on current page
        if ($this->search !== '') {
            $term = mb_strtolower(trim($this->search));
            $emails = $emails->filter(function ($e) use ($term) {
                return str_contains(mb_strtolower($e['from_name'] ?? ''), $term)
                    || str_contains(mb_strtolower($e['from_email'] ?? ''), $term)
                    || str_contains(mb_strtolower($e['subject'] ?? ''), $term);
            });
        }

        $uids = $emails->pluck('uid')->map(fn ($u) => (string) $u)->all();
        $emailLogs = \App\Models\StockEmailLog::whereIn('email_uid', $uids)->get()->keyBy('email_uid');

        // Satu query agregat menggantikan 5 query terpisah (3x COUNT berkondisi,
        // 1x COUNT total, 1x ambil baris terakhir) yang sebelumnya dijalankan
        // pada SETIAP render halaman ini.
        $agg = \App\Models\StockEmailLog::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success")
            ->selectRaw("SUM(CASE WHEN status = 'partial_unmapped' THEN 1 ELSE 0 END) as partial")
            ->selectRaw("SUM(CASE WHEN status NOT IN ('success', 'partial_unmapped') THEN 1 ELSE 0 END) as failed")
            ->selectRaw('MAX(created_at) as last_run')
            ->first();

        $automationStats = [
            'total_processed' => (int) ($agg->total ?? 0),
            'success_count' => (int) ($agg->success ?? 0),
            'partial_count' => (int) ($agg->partial ?? 0),
            'failed_count' => (int) ($agg->failed ?? 0),
            // Blade memanggil ->diffForHumans(), jadi tipenya harus tetap Carbon.
            'last_run' => ! empty($agg?->last_run) ? \Illuminate\Support\Carbon::parse($agg->last_run) : null,
        ];
        $recentLogs = $this->showLogsModal ? \App\Models\StockEmailLog::latest()->take(50)->get() : collect();

        return view('livewire.emails.index', [
            'isConfigured' => $isConfigured,
            'emails' => $emails->values(),
            'emailLogs' => $emailLogs,
            'automationStats' => $automationStats,
            'recentLogs' => $recentLogs,
            'total' => $inboxResult['total'] ?? 0,
            'currentPage' => $inboxResult['current_page'] ?? 1,
            'lastPage' => $inboxResult['last_page'] ?? 1,
            'hasError' => ! empty($inboxResult['error']),
            'errorMessage' => $inboxResult['message'] ?? null,
            'stockKeywords' => config('imap.stock_subject_keywords', ['Satoria Daily Stock']),
        ])->layout('layouts.app', ['title' => 'Inbox Email Distributor']);
    }
}
