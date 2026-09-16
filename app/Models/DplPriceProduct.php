<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DplPriceProduct extends Model
{
    use HasFactory;

    protected $table = 'dpl_price_product';

    public $incrementing = false;

    protected $primaryKey = 'id_product';

    protected $keyType = 'string';

    protected $fillable = [
        'id_product',
        'id_price_region',
        'price',
        'price_reguler',
        'dump_update_harga',
        'netsuite_id',
        'netsuite_item_id',
    ];

    protected $casts = [
        'price' => 'decimal:4',
        'price_reguler' => 'integer',
        'id_price_region' => 'integer',
    ];
}
