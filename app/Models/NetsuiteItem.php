<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class NetsuiteItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'internal_id',
        'netsuite_id',
        'netsuite_name',
        'default_satuan',
    ];

    public function distributorItems(): HasMany
    {
        return $this->hasMany(DistributorItem::class);
    }

    public function dplPrices(): HasMany
    {
        return $this->hasMany(DplPriceProduct::class, 'netsuite_item_id');
    }

    public function dplPrice(): HasOne
    {
        return $this->hasOne(DplPriceProduct::class, 'netsuite_item_id')->where('id_price_region', 1);
    }

    public function getUnitPriceAttribute(): float
    {
        return (float) ($this->dplPrice?->price ?? 0.0);
    }
}

