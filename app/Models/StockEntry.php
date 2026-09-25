<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A snapshot row: `quantity` is the stock figure AS OF `tanggal` for one
 * distributor_item at one distributor. Uploading the same (tanggal,
 * distributor_item_id) again overwrites the row — see the unique key
 * `stock_entries_snapshot_key` in the migration.
 */
class StockEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'tanggal',
        'distributor_id',
        'distributor_item_id',
        'quantity',
        'satuan',
        'quantity_asli',
        'satuan_asli',
        'expired_date',
        'batch_no',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'expired_date' => 'date',
            'quantity' => 'decimal:2',
            'quantity_asli' => 'decimal:2',
        ];
    }

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class)->withTrashed();
    }

    public function distributorItem(): BelongsTo
    {
        return $this->belongsTo(DistributorItem::class)->withTrashed();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Nama produk untuk laporan: nama NetSuite. Nama versi distributor hanya
     * dipakai bila itemnya belum ter-mapping (belum ada padanan NetSuite).
     */
    public function displayName(): string
    {
        return $this->distributorItem?->netsuiteItem?->netsuite_name
            ?? $this->distributorItem?->item_name
            ?? '—';
    }

    /** Satuan untuk laporan: Satuan Default NetSuite, bila kosong satuan baris ini. */
    public function displayUnit(): ?string
    {
        return $this->distributorItem?->netsuiteItem?->default_satuan ?: ($this->satuan ?: null);
    }

    public function daysToExpiry(): ?int
    {
        if (! $this->expired_date) {
            return null;
        }

        return (int) Carbon::today()->diffInDays($this->expired_date, false);
    }

    /**
     * Ambang kedaluwarsa — SATU-SATUNYA sumber kebenaran.
     *
     * Sebelumnya ada dua definisi yang berbeda: metode ini memakai 30/90 hari
     * sementara tab FEFO dan export CSV memakai 90/180, sehingga angka di kartu
     * KPI tidak pernah cocok dengan tabel di bawahnya. 90/180 dipilih sebagai
     * kanonik karena label di UI memang berbunyi "< 3 Bulan" dan "3-6 Bulan",
     * dan horizon tersebut lebih bermakna untuk distribusi farmasi.
     */
    public const CRITICAL_DAYS = 90;

    public const WARNING_DAYS = 180;

    /**
     * @return 'unknown'|'expired'|'critical'|'warning'|'safe'
     */
    public function expiryStatus(): string
    {
        $days = $this->daysToExpiry();

        if ($days === null) {
            return 'unknown';
        }
        if ($days < 0) {
            return 'expired';
        }
        if ($days <= self::CRITICAL_DAYS) {
            return 'critical';
        }
        if ($days <= self::WARNING_DAYS) {
            return 'warning';
        }

        return 'safe';
    }
}
