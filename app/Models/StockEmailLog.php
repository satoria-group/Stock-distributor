<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockEmailLog extends Model
{
    use HasFactory;

    /**
     * Status yang berarti data email ini SUDAH masuk ke database (seluruhnya
     * atau sebagian). Email berstatus ini tidak boleh diproses ulang otomatis.
     *
     * partial_unmapped termasuk di sini: sebagian cabang/item sudah tersimpan,
     * dan sisanya (item belum ter-mapping, cabang yang datanya sudah ada)
     * adalah keputusan manusia di halaman Upload, bukan tugas cron.
     */
    public const IMPORTED_STATUSES = ['success', 'partial_unmapped', 'manual_import'];

    protected $fillable = [
        'email_uid',
        'message_id',
        'from_email',
        'from_name',
        'subject',
        'distributor_id',
        'distributor_code',
        'tanggal_snapshot',
        'filename',
        'file_size_bytes',
        'status',
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
            'file_size_bytes' => 'integer',
            'total_rows' => 'integer',
            'imported_rows' => 'integer',
            'skipped_rows' => 'integer',
            'details' => 'array',
        ];
    }

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }
}
