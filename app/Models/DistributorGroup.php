<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Grup usaha distributor — "siapa"-nya, dipakai untuk mengelompokkan dashboard.
 *
 * Jangan dikelirukan dengan DistributorTemplateGroup, yang mengatur "bagaimana"
 * berkas Excel-nya dibaca. Bentuk berkas menempel pada grup ini karena seluruh
 * cabang satu grup usaha mengirim susunan kolom yang sama; satu cabang yang
 * menyimpang tetap bisa menimpanya lewat kolomnya sendiri (lihat
 * Distributor::effectiveTemplateGroup()).
 */
class DistributorGroup extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Palet warna chart.
     *
     * Warna dipilih dari daftar tertutup, bukan diketik bebas: chart dashboard
     * memerlukan warna yang cukup kontras satu sama lain dan tetap terbaca pada
     * latar terang — dua hal yang mudah rusak oleh pilihan bebas.
     *
     * @var array<string, string>  kode warna => nama yang ditampilkan
     */
    public const COLORS = [
        '#3b82f6' => 'Biru',
        '#a855f7' => 'Ungu',
        '#f97316' => 'Oranye',
        '#84cc16' => 'Hijau',
        '#06b6d4' => 'Tosca',
        '#ec4899' => 'Merah muda',
        '#eab308' => 'Kuning',
        '#14b8a6' => 'Teal',
        '#ef4444' => 'Merah',
        '#6366f1' => 'Indigo',
        '#8b5cf6' => 'Violet',
        '#0ea5e9' => 'Biru langit',
    ];

    /** Warna untuk distributor yang belum bergrup. */
    public const UNGROUPED_COLOR = '#94a3b8';

    /** Label untuk distributor yang belum bergrup. */
    public const UNGROUPED_LABEL = 'Lainnya';

    protected $fillable = [
        'name',
        'is_active',
        'color',
        'sort_order',
        'template_group_id',
        'sender_email',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function distributors(): HasMany
    {
        return $this->hasMany(Distributor::class);
    }

    /** Bentuk berkas Excel yang dipakai seluruh cabang grup ini. */
    public function templateGroup(): BelongsTo
    {
        return $this->belongsTo(DistributorTemplateGroup::class, 'template_group_id');
    }

    /** Pemetaan item yang berlaku untuk seluruh cabang grup ini. */
    public function items(): HasMany
    {
        return $this->hasMany(DistributorItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Urutan baku di seluruh filter dan chart. */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function colorOrDefault(): string
    {
        return $this->color ?: self::UNGROUPED_COLOR;
    }

    /**
     * Warna berikutnya yang belum terpakai, untuk grup baru.
     *
     * Dua grup berwarna sama membuat chart tidak terbaca, jadi warna dipilihkan
     * daripada dibiarkan bertabrakan karena kelalaian.
     */
    public static function nextAvailableColor(): string
    {
        $used = static::query()->whereNotNull('color')->pluck('color')->all();
        $available = array_diff(array_keys(self::COLORS), $used);

        return $available !== [] ? (string) reset($available) : (string) array_key_first(self::COLORS);
    }
}
