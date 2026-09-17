<?php

namespace App\Support;

class Search
{
    /**
     * Ubah kata kunci dari pengguna menjadi pola LIKE/ILIKE yang aman.
     *
     * Tanpa ini, karakter wildcard yang diketik pengguna ikut ditafsirkan
     * sebagai wildcard SQL: mengetik "%" mencocokkan SELURUH baris, dan "_"
     * mencocokkan sembarang satu karakter. Bukan celah injeksi (nilainya tetap
     * di-bind sebagai parameter), tapi hasil pencariannya menyesatkan.
     *
     * Backslash di-escape lebih dulu supaya tidak merusak escape berikutnya.
     * PostgreSQL memakai '\' sebagai karakter escape bawaan pada LIKE/ILIKE,
     * jadi tidak perlu klausa ESCAPE tambahan.
     */
    public static function escapeLike(string $term): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
    }

    /**
     * Pola "mengandung" yang siap dipakai: %kata kunci%
     */
    public static function contains(?string $term): string
    {
        return '%'.self::escapeLike(trim((string) $term)).'%';
    }
}
