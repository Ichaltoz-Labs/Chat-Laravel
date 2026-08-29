# JamSholat — TempChat (Temporary Chat Room)

Aplikasi temporary chat room berbasis Laravel 13. Room otomatis kadaluarsa setelah 24 jam dan dibersihkan oleh scheduler. Spesifikasi lengkap ada di `.agents/`.

## Fitur

- Buat room chat sementara dengan kode 8 karakter (collision-resistant).
- Join cukup isi nama — tanpa login/registrasi.
- Pesan realtime via polling 2 detik: pesan baru, typing indicator, online presence.
- Sanitasi XSS (`mews/purifier`, semua HTML distrip), rate limit 1 pesan/detik/user.
- Room kadaluarsa 24 jam → 404; command `rooms:cleanup` hapus room expired.
- Copy link, share ke WhatsApp/Telegram, notifikasi suara, auto-scroll, countdown.

## Requirements

- PHP 8.3 + Composer
- Node.js + npm
- MySQL (atau SQLite — ubah `.env`)

## Setup

```sh
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate
```

## Menjalankan (Development)

```sh
composer dev
```

Menjalankan server `php artisan serve` + Vite (hot reload). Buka `http://localhost:8000`.

## Menjalankan scheduler (cleanup room expired)

Room dihapus otomatis tiap menit oleh command `rooms:cleanup`.

Dev (foreground):

```sh
php artisan schedule:work
```

Production (cron, jalankan sekali):

```
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

Jika perlu, cek manual:

```sh
php artisan rooms:cleanup
```

## Testing

```sh
composer test            # config:clear lalu php artisan test (SQLite in-memory, tidak perlu DB server)
vendor/bin/phpunit       # runner PHPUnit langsung
php artisan test --filter ChatTest
```

## Alur demo

1. Buka `/` → isi nama → klik **Create Room** → diarahkan ke halaman room.
2. Salin link dan buka di tab/jendela lain (incognito) → join dengan nama berbeda.
3. Kirim pesan dari satu tab, lihat muncul di tab lain tanpa reload (±2 detik).
4. Perhatikan typing indicator, suara beep, countdown 24 jam, dan online presence.
5. **Leave Room** → system message muncul, nama hilang dari daftar online.

## Code style

```sh
vendor/bin/pint
```

## Struktur penting

- `app/Services/RoomService.php` — logika room (generate code, join, unique name, system message)
- `app/Http/Controllers/RoomController.php`, `ChatController.php` — endpoint web
- `app/Http/Middleware/CheckRoomExpired.php` — 404 bila room tidak ada/sudah expired
- `app/Console/Commands/CleanupExpiredRooms.php` + `routes/console.php` — cleanup berjadwal
- `resources/js/room.js` — polling, typing debounce, countdown, share
- `.agents/` — PRD, design system, task breakdown