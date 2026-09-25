<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu kiriman ke POST /api/v1/stock, beserta hasilnya. */
class StockApiLog extends Model
{
    protected $fillable = [
        'api_client_id',
        'request_id',
        'ip_address',
        'tanggal_snapshot',
        'status',
        'branch_count',
        'total_rows',
        'imported_rows',
        'skipped_rows',
        'error_message',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_snapshot' => 'date',
            'details' => 'array',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class, 'api_client_id');
    }
}
