# Stock Distributor — Satoria Group

Aplikasi Laravel 11 + PostgreSQL untuk master data distributor/produk, upload stock harian dengan grid editable, dan dashboard analisa stock.

## Setup

```bash
composer install
cp .env.example .env   # lalu isi DB_* sesuai server Anda, atau pakai .env yang sudah ada
php artisan key:generate
php artisan migrate
php artisan db:seed     # import role, 3 user demo, dan master data dari database/seeders/data/stock_distributor_source.xlsx
npm install
npm run build
php artisan serve
```

## Login demo

| Role      | Email                          | Password    |
|-----------|---------------------------------|-------------|
| Admin     | admin@satoriagroup.co.id       | satoria123  |
| Sales     | sales@satoriagroup.co.id       | satoria123  |
| Logistik  | logistik@satoriagroup.co.id    | satoria123  |

**Ganti semua password ini sebelum dipakai di production.**

## Catatan kualitas data (dari file Stock Distributor.xlsx yang diupload)

1. **51 baris di sheet "Distributor Item"** mereferensikan kode distributor yang tidak ada di sheet "Distributor" — terbanyak `KFTDMAKASAR` (14 baris). Baris-baris ini dilewati saat seeding. Tambahkan distributor tersebut secara manual di menu Master Distributor, lalu re-import kalau perlu.
2. **Sheet "Template"** (contoh format upload harian) menggunakan penulisan nama item yang sedikit berbeda dari sheet "Distributor Item" untuk distributor yang sama (contoh: "RINGER LACTATE INFUS @ 500 ML" vs "RINGER LACTATE 500 mL" di Master Mapping) — dari 107 baris di Template, hanya 15 yang otomatis ketemu match-nya saat diuji. Ini bukan bug aplikasi: nama item di file upload harian distributor perlu konsisten dengan nama yang sudah terdaftar di Master Mapping (menu "Mapping Item Distributor") agar bisa ter-parse otomatis. Item yang tidak dikenali akan ditampilkan sebagai peringatan (bukan disimpan diam-diam) saat import.
3. **21 item di Master Mapping** masih belum ter-mapping ke produk Netsuite (semuanya kategori alat/hardware: catheter, infusion set) — sesuai keputusan yang sudah dikonfirmasi, item ini tetap bisa di-upload stock-nya, hanya ditandai "Belum ter-mapping".

## Struktur Modul

- `app/Models` — Distributor, NetsuiteItem, DistributorItem, StockEntry, User (+ Spatie roles)
- `app/Livewire` — semua modul CRUD & dashboard (Livewire 3 full-page components)
- `app/Policies` — otorisasi per role (Admin/Sales/Logistik), mengikuti matriks akses yang sudah disepakati
- `database/seeders/MasterDataSeeder.php` — import dari xlsx, idempotent (aman dijalankan ulang)
