<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
}
