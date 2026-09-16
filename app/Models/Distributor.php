<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Distributor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'distributor_code',
        'name',
        'sender_email',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function stockEmailLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StockEmailLog::class);
    }
}
