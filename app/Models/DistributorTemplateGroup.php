<?php

namespace App\Models;

use App\Support\StockColumnRecipe;
use App\Support\StockTemplateColumns;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Satu "bentuk berkas Excel" yang dipakai bersama oleh sekelompok distributor.
 *
 * Alasan pengelompokan: template tidak berbeda per distributor melainkan per
 * grup usaha — seluruh cabang UDC mengirim berkas dengan judul kolom yang
 * sama. Menyimpan pemetaan di level grup berarti satu kali setelan berlaku
 * untuk puluhan cabang, dan cabang baru cukup ditempelkan ke grupnya.
 */
class DistributorTemplateGroup extends Model
{
    use HasFactory, SoftDeletes;

    /** Tanggal snapshot dibaca apa adanya dari satu kolom tanggal. */
    public const DATE_AUTO = 'auto';

    /** Satu kolom, tapi formatnya harus dipaksa (date_format). */
    public const DATE_FORMAT = 'format';

    /**
     * Tanggal terpecah beberapa kolom berurutan: hari, bulan, tahun.
     *
     * TIDAK lagi dipilih operator: cara ini disimpulkan sendiri dari resep
     * kolom Tanggal yang merangkai tiga kolom atau lebih. Nilainya masih ada
     * demi baris lama yang sudah tersimpan, dan diperlakukan sama dengan
     * 'auto' — keputusannya kini ada pada resep, bukan pada setelan ini.
     */
    public const DATE_PARTS_DMY = 'parts_dmy';

    /** Kolom periode hanya berisi bulan; snapshot memakai akhir bulan itu. */
    public const DATE_MONTH = 'month';

    /**
     * Contoh format, ditulis dengan notasi yang dipakai orang sehari-hari.
     *
     * @var array<string, string>
     */
    public const FORMAT_EXAMPLES = [
        'dd/mm/yyyy' => '31/12/2027',
        'dd-mm-yyyy' => '31-12-2027',
        'yyyy-mm-dd' => '2027-12-31',
        'mm/yyyy' => '12/2027',
        'mm-yyyy' => '12-2027',
        'yyyy-mm' => '2027-12',
        'mmm-yyyy' => 'Dec-2027',
    ];

    /**
     * Terjemahkan notasi yang diketik operator menjadi format PHP.
     *
     * Operator menulis apa yang dilihatnya di berkas — 'dd/mm/yyyy', bukan
     * 'd/m/Y'. Memaksa lambang PHP membuat 'yyyy/mm' diterima sebagai sesuatu
     * yang sama sekali lain (tahun dua digit empat kali: 27272727), dan
     * kekeliruan seperti itu tidak terlihat sampai impor menghasilkan tanggal
     * kosong.
     *
     * Notasi PHP yang sudah tersimpan tetap dihormati: nilai tanpa huruf
     * berulang (mis. 'd/m/Y') diteruskan apa adanya.
     */
    public static function toPhpFormat(?string $input): ?string
    {
        $input = trim((string) $input);

        if ($input === '') {
            return null;
        }

        // Tanpa huruf berulang berarti ini sudah notasi PHP.
        if (! preg_match('/(dd|mm|yy)/i', $input)) {
            return $input;
        }

        $map = [
            'yyyy' => 'Y',
            'yy' => 'y',
            'mmmm' => 'F',
            'mmm' => 'M',
            'mm' => 'm',
            'dd' => 'd',
            'm' => 'n',
            'd' => 'j',
            'y' => 'y',
        ];

        // Token terpanjang lebih dulu, supaya 'mmm' tidak keburu termakan 'mm'.
        return preg_replace_callback(
            '/yyyy|mmmm|mmm|mm|dd|yy|m|d|y/i',
            fn ($m) => $map[mb_strtolower($m[0])] ?? $m[0],
            $input
        ) ?? $input;
    }

    /** Format ini tidak menyebut hari? */
    public static function formatIsMonthOnly(?string $format): bool
    {
        $php = self::toPhpFormat($format);

        if (! $php) {
            return false;
        }

        $stripped = preg_replace('/\\\\./', '', $php) ?? $php;

        return ! preg_match('/[dj]/', $stripped);
    }

    /**
     * Format ini benar-benar bisa dipakai membaca tanggal?
     *
     * Tiga syarat, dan syarat ketiga yang paling penting:
     *
     *  1. Ada lambang bulan dan ada lambang tahun. Tanpa ini 'dd' sendirian
     *     akan lolos padahal tidak menunjuk tanggal mana pun.
     *  2. Tidak menyisakan huruf yang bukan lambang tanggal.
     *  3. Sebuah tanggal ditulis dengan format itu lalu dibaca kembali, dan
     *     harus kembali utuh.
     */
    public static function formatIsValid(?string $format): bool
    {
        $php = self::toPhpFormat($format);

        if (! $php) {
            return true;
        }

        $stripped = preg_replace('/\\\\./', '', $php) ?? $php;

        if (! preg_match('/[mnMF]/', $stripped) || ! preg_match('/[Yy]/', $stripped)) {
            return false;
        }

        // Huruf di luar lambang tanggal yang kita dukung: hampir pasti salah
        // ketik, dan hasilnya tidak akan seperti yang dikira operator.
        if (preg_match('/[a-ce-lo-xzA-CE-LNO-XZ]/', $stripped)) {
            return false;
        }

        try {
            $probe = Carbon::create(2027, 12, 31)?->format($php);
            if (! $probe) {
                return false;
            }

            $parsed = Carbon::createFromFormat('!'.$php, $probe);

            return $parsed !== false && $parsed->format($php) === $probe;
        } catch (\Throwable) {
            return false;
        }
    }

    /** Format Tanggal snapshot dalam lambang PHP, siap dipakai membaca. */
    public function phpDateFormat(): ?string
    {
        return self::toPhpFormat($this->date_format);
    }

    /** Format ED dalam lambang PHP, siap dipakai membaca. */
    public function phpEdFormat(): ?string
    {
        return self::toPhpFormat($this->ed_format);
    }

    protected $fillable = [
        'name',
        'is_active',
        'notes',
        'column_map',
        'header_row',
        'sheet_name',
        'date_mode',
        'date_format',
        'ed_format',
        'default_batch',
        'skip_nonpositive_qty',
    ];

    protected function casts(): array
    {
        return [
            'column_map' => 'array',
            'header_row' => 'integer',
            'skip_nonpositive_qty' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Grup yang ikut dicobakan saat membaca berkas.
     *
     * Grup nonaktif sengaja disingkirkan dari pencocokan: resep setengah jadi
     * bisa saja "berhasil" membaca berkas milik grup lain dan menyesatkan
     * hasil impor tanpa ada yang menyadarinya.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function distributors(): HasMany
    {
        return $this->hasMany(Distributor::class, 'template_group_id');
    }

    /**
     * Resep pembentuk nilai per kolom kanonik.
     *
     * Ini satu-satunya pintu masuk ke column_map: baik bentuk lama (teks polos
     * = satu judul kolom) maupun bentuk resep dinormalkan menjadi objek yang
     * sama, sehingga pemanggil tidak pernah perlu tahu bedanya.
     *
     * @return array<string, StockColumnRecipe>
     */
    public function recipes(): array
    {
        $out = [];
        foreach ((array) ($this->column_map ?? []) as $canonical => $value) {
            if (! in_array($canonical, StockTemplateColumns::all(), true)) {
                continue;
            }

            $recipe = StockColumnRecipe::fromValue($value);
            if ($recipe) {
                $out[$canonical] = $recipe;
            }
        }

        return $out;
    }

    /** Jumlah kolom kanonik yang sudah dipetakan manual. */
    public function mappedColumnCount(): int
    {
        return count($this->recipes());
    }

    /**
     * Cara membaca kolom Tanggal — DISIMPULKAN, bukan disetel terpisah.
     *
     * Format yang dipilih sudah menyatakan segalanya: format bulan/tahun
     * berarti periode bulanan, format lain berarti format tertentu, dan tanpa
     * format berarti deteksi otomatis. Setelan terpisah hanya akan menjadi
     * sumber kebenaran kedua yang bisa bertentangan dengan yang pertama.
     */
    public function dateMode(): string
    {
        if ($this->date_format) {
            return self::formatIsMonthOnly($this->date_format) ? self::DATE_MONTH : self::DATE_FORMAT;
        }

        // Baris lama yang menyimpan mode bulanan tanpa format: perilakunya
        // dipertahankan, bentuk bulannya ditebak otomatis.
        return $this->date_mode === self::DATE_MONTH ? self::DATE_MONTH : self::DATE_AUTO;
    }

    /** Resep Tanggal merangkai beberapa kolom (hari, bulan, tahun)? */
    public function tanggalFromMultipleColumns(): bool
    {
        $recipe = $this->recipes()['Tanggal'] ?? null;

        return $recipe !== null && count($recipe->columns()) >= 3;
    }

    /**
     * Ringkasan setelan untuk ditampilkan di daftar grup.
     *
     * @return array<int, string>
     */
    public function settingLabels(): array
    {
        $out = [];

        $out[] = match (true) {
            $this->tanggalFromMultipleColumns() => 'Tanggal dirakit dari beberapa kolom',
            $this->dateMode() === self::DATE_MONTH => 'Tanggal bulanan ('.($this->date_format ?: 'otomatis').') → akhir bulan',
            $this->dateMode() === self::DATE_FORMAT => 'Format tanggal '.$this->date_format,
            default => 'Tanggal otomatis',
        };

        $out[] = $this->ed_format
            ? 'Format ED '.$this->ed_format.(self::formatIsMonthOnly($this->ed_format) ? ' → tanggal 1' : '')
            : 'ED otomatis';

        if ($this->default_batch) {
            $out[] = 'Batch pengganti: '.$this->default_batch;
        }

        if ($this->skip_nonpositive_qty) {
            $out[] = 'Abaikan qty ≤ 0';
        }

        return $out;
    }
}
