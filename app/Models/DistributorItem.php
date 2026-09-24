<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class DistributorItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'distributor_id',
        'distributor_group_id',
        'source_item_id',
        'item_name',
        'satuan',
        'netsuite_item_id',
    ];

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }

    /**
     * Grup usaha pemilik pemetaan ini.
     *
     * Satu baris milik grup berlaku untuk SELURUH cabangnya — nama item sama
     * persis di semua cabang karena memang datang dari satu berkas.
     */
    public function distributorGroup(): BelongsTo
    {
        return $this->belongsTo(DistributorGroup::class);
    }

    /**
     * Pemetaan yang berlaku untuk sebuah distributor: milik grupnya, ditambah
     * pengecualian milik cabang itu sendiri.
     */
    public function scopeForDistributor($query, Distributor $distributor)
    {
        return $query->where(function ($q) use ($distributor) {
            $q->where('distributor_id', $distributor->id);

            if ($distributor->distributor_group_id) {
                $q->orWhere('distributor_group_id', $distributor->distributor_group_id);
            }
        });
    }

    /** Pemetaan ini berlaku untuk satu cabang saja? */
    public function isBranchException(): bool
    {
        return $this->distributor_id !== null;
    }

    /**
     * Daftar pemetaan sebuah distributor, siap dicari berdasarkan nama item.
     *
     * Pengecualian cabang menimpa pemetaan grup untuk nama yang sama — itulah
     * gunanya pengecualian.
     *
     * @return \Illuminate\Support\Collection<string, static>
     */
    public static function lookupFor(Distributor $distributor): \Illuminate\Support\Collection
    {
        return static::forDistributor($distributor)
            ->orderByRaw('distributor_id nulls first')
            ->get()
            ->keyBy(fn ($i) => static::normalizeName($i->item_name));
    }

    /**
     * Masukkan sebuah nama item ke antrean pemetaan milik distributor ini.
     *
     * Satu tempat untuk semua jalur yang menemukan item baru — impor berkas,
     * tombol dari halaman Email, dan permintaan mapping dari halaman Upload —
     * karena ketiganya dulu mengulang logika yang sama dengan perbedaan halus
     * yang tidak disengaja.
     *
     * Dua aturan yang dijaga di sini:
     *
     *  1. Baris yang pernah dihapus DIPULIHKAN, bukan diganti baris baru.
     *     Snapshot stok menunjuk ke baris itu; membuat baris kedua memecah satu
     *     item menjadi dua di seluruh laporan.
     *  2. Pemulihan TIDAK membawa pemetaan lamanya. Menghapus baris pemetaan
     *     hampir selalu berarti "nama ini salah" atau "pemetaannya keliru";
     *     menghidupkan kembali pemetaan lama sama saja membatalkan keputusan itu
     *     diam-diam. Item kembali sebagai belum ter-mapping.
     */
    public static function queueFor(Distributor $distributor, string $rawName, ?string $satuan = null): ?static
    {
        $rawName = trim($rawName);
        if ($rawName === '') {
            return null;
        }

        // Antrean milik GRUP bila distributornya bergrup: nama item sama persis
        // di seluruh cabang, jadi satu baris sudah mewakili semuanya.
        $owner = $distributor->distributor_group_id
            ? ['distributor_group_id' => $distributor->distributor_group_id]
            : ['distributor_id' => $distributor->id];

        $normalized = static::normalizeName($rawName);

        if ($found = static::findForOwner($owner, $normalized)) {
            // Satuan ikut berkas TERBARU yang menyebutkannya, bukan terkunci
            // pada berkas pertama.
            if ($satuan && $satuan !== $found->satuan) {
                $found->update(['satuan' => $satuan]);
            }

            return $found;
        }

        try {
            // DB::transaction() bersarang = SAVEPOINT. Ini WAJIB di PostgreSQL:
            // begitu sebuah statement gagal, seluruh transaksi masuk status
            // aborted dan query berikutnya ditolak (SQLSTATE 25P02) — termasuk
            // query pemulihan di blok catch. Savepoint membuat kegagalan insert
            // bisa dibatalkan sendirian.
            return DB::transaction(fn () => static::create($owner + [
                'item_name' => $rawName,
                'satuan' => $satuan ?: null,
                'netsuite_item_id' => null,
            ]));
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            // Dua proses menambahkan nama yang sama berbarengan.
            return static::findForOwner($owner, $normalized);
        }
    }

    /**
     * Cari baris milik pemilik tertentu, pulihkan bila pernah dihapus.
     *
     * @param  array<string, int>  $owner
     */
    private static function findForOwner(array $owner, string $normalized): ?static
    {
        $existing = static::withTrashed()
            ->where($owner)
            ->whereRaw('LOWER(TRIM(item_name)) = ?', [$normalized])
            ->first();

        if (! $existing) {
            return null;
        }

        if ($existing->trashed()) {
            $existing->restore();
            $existing->update(['netsuite_item_id' => null]);
        }

        return $existing;
    }

    /** Bentuk pembanding nama item — spasi berlebih dan huruf besar diabaikan. */
    public static function normalizeName(?string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $name) ?? ''));
    }

    public function netsuiteItem(): BelongsTo
    {
        return $this->belongsTo(NetsuiteItem::class);
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
