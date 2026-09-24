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
        'template_group_id',
        'distributor_group_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** Grup template Excel yang dipakai distributor ini (lihat DistributorTemplateGroup). */
    public function templateGroup(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DistributorTemplateGroup::class, 'template_group_id');
    }

    /** Grup usaha distributor ini (lihat DistributorGroup). */
    public function group(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DistributorGroup::class, 'distributor_group_id');
    }

    /**
     * Bentuk berkas Excel yang berlaku untuk distributor ini.
     *
     * Template menempel pada GRUP USAHA, karena seluruh cabang satu grup
     * mengirim susunan kolom yang sama — satu kali setelan berlaku untuk
     * puluhan cabang. Kolom di distributor ini sendiri berperan sebagai
     * PENGECUALIAN, untuk cabang yang formatnya menyimpang dari grupnya.
     */
    public function effectiveTemplateGroup(): ?DistributorTemplateGroup
    {
        return $this->templateGroup ?? $this->group?->templateGroup;
    }

    /** Grup template ini diwarisi dari grup usaha, bukan disetel khusus? */
    public function templateIsInherited(): bool
    {
        return $this->template_group_id === null && $this->group?->template_group_id !== null;
    }

    /**
     * Whitelist email pengirim yang berlaku untuk distributor ini.
     *
     * Satu berkas berisi banyak cabang selalu datang dari satu alamat, jadi
     * whitelist wajarnya milik grup. Kolom di cabang tetap ada sebagai
     * pengecualian, untuk cabang yang benar-benar mengirim dari alamat lain.
     */
    public function effectiveSenderEmail(): ?string
    {
        $own = trim((string) ($this->sender_email ?? ''));

        return $own !== '' ? $own : ($this->group?->sender_email ?: null);
    }

    /** Whitelist ini diwarisi dari grup, bukan disetel khusus? */
    public function senderEmailIsInherited(): bool
    {
        return trim((string) ($this->sender_email ?? '')) === '' && ($this->group?->sender_email ?: null) !== null;
    }

    public function stockEmailLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StockEmailLog::class);
    }
}
