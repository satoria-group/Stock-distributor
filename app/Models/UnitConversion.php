<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Konversi satuan berkas ke satuan stok, mis. 1 BOX = 50 PCS.
 *
 * Diterapkan SAAT IMPOR: quantity & satuan di stock_entries sudah dalam
 * satuan tujuan, nilai aslinya disimpan di quantity_asli & satuan_asli.
 */
class UnitConversion extends Model
{
    protected $fillable = ['from_unit', 'to_unit', 'factor'];

    protected function casts(): array
    {
        return ['factor' => 'float'];
    }

    /** @var array<string, array{to: string, factor: float}>|null */
    private static ?array $cache = null;

    public static function normalizeUnit(?string $unit): string
    {
        return mb_strtoupper(trim((string) $unit));
    }

    /**
     * @return array{quantity: float, satuan: ?string, quantity_asli: ?float, satuan_asli: ?string}
     */
    public static function apply(float $quantity, ?string $satuan): array
    {
        $rule = self::rules()[self::normalizeUnit($satuan)] ?? null;

        if ($rule === null) {
            return ['quantity' => $quantity, 'satuan' => $satuan, 'quantity_asli' => null, 'satuan_asli' => null];
        }

        return [
            'quantity' => $quantity * $rule['factor'],
            'satuan' => $rule['to'],
            'quantity_asli' => $quantity,
            'satuan_asli' => $satuan,
        ];
    }

    /** @return array<string, array{to: string, factor: float}> */
    private static function rules(): array
    {
        return self::$cache ??= static::query()->get()
            ->mapWithKeys(fn (self $c) => [self::normalizeUnit($c->from_unit) => [
                'to' => self::normalizeUnit($c->to_unit),
                'factor' => (float) $c->factor,
            ]])
            ->all();
    }

    protected static function booted(): void
    {
        $flush = fn () => self::$cache = null;
        static::saved($flush);
        static::deleted($flush);
    }
}
