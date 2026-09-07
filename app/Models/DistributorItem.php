<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DistributorItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'distributor_id',
        'source_item_id',
        'item_name',
        'satuan',
        'netsuite_item_id',
        'netsuite_satuan',
    ];

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }

    public function netsuiteItem(): BelongsTo
    {
        return $this->belongsTo(NetsuiteItem::class);
    }

    public function stockEntries(): HasMany
    {
        return $this->hasMany(StockEntry::class);
    }

    public function isMapped(): bool
    {
        return $this->netsuite_item_id !== null;
    }

    public function scopeUnmapped($query)
    {
        return $query->whereNull('netsuite_item_id');
    }

    public function scopeMapped($query)
    {
        return $query->whereNotNull('netsuite_item_id');
    }
}
