<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Pemegang token API push stok. Satu klien mewakili satu grup usaha dan hanya
 * boleh mengirim data untuk cabang-cabang grup tersebut.
 */
class ApiClient extends Model
{
    protected $fillable = [
        'name',
        'distributor_group_id',
        'token_hash',
        'allowed_ips',
        'is_active',
        'last_used_at',
    ];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(DistributorGroup::class, 'distributor_group_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(StockApiLog::class);
    }

    public static function hashToken(string $plain): string
    {
        return hash('sha256', $plain);
    }

    /**
     * Buat token baru untuk klien ini. Token asli dikembalikan SEKALI dan
     * tidak disimpan; token lama langsung tidak berlaku.
     */
    public function issueToken(): string
    {
        $plain = 'stk_'.Str::random(48);
        $this->forceFill(['token_hash' => self::hashToken($plain)])->save();

        return $plain;
    }

    /**
     * Daftarkan klien baru sekaligus tokennya.
     *
     * @return array{0: self, 1: string}  klien dan token aslinya
     */
    public static function register(array $attributes): array
    {
        $plain = 'stk_'.Str::random(48);
        $client = static::create($attributes + ['token_hash' => self::hashToken($plain)]);

        return [$client, $plain];
    }

    public static function findByToken(?string $plain): ?self
    {
        if ($plain === null || $plain === '') {
            return null;
        }

        return static::where('token_hash', self::hashToken($plain))->first();
    }

    /** IP pemanggil diizinkan? Daftar kosong berarti semua IP boleh. */
    public function allowsIp(?string $ip): bool
    {
        $list = array_filter(array_map('trim', explode(',', (string) $this->allowed_ips)));

        return $list === [] || in_array($ip, $list, true);
    }
}
