<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockSnapshotActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'tanggal',
        'distributor_id',
        'user_id',
        'action',
        'description',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'metadata' => 'array',
        ];
    }

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForSnapshot($query, string $tanggal, int $distributorId)
    {
        return $query->where('tanggal', $tanggal)
            ->where('distributor_id', $distributorId);
    }

    public function scopeAutomation($query)
    {
        return $query->where('action', 'automation');
    }

    public function scopeReviews($query)
    {
        return $query->where('action', 'review');
    }
}
