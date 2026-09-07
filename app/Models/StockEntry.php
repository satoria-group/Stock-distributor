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
        ];
    }

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }

    public function distributorItem(): BelongsTo
    {
        return $this->belongsTo(DistributorItem::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function daysToExpiry(): ?int
    {
        if (! $this->expired_date) {
            return null;
        }

        return (int) Carbon::today()->diffInDays($this->expired_date, false);
    }

    public function expiryStatus(): string
    {
        $days = $this->daysToExpiry();

        if ($days === null) {
            return 'unknown';
        }
        if ($days < 0) {
            return 'expired';
        }
        if ($days <= 30) {
            return 'critical';
        }
        if ($days <= 90) {
            return 'warning';
        }

        return 'ok';
    }
}
