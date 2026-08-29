# Task Checklist — Temporary Chat Room (TempChat)

> Sumber acuan: `.agents/prd.md` · `.agents/design.md`
> Aturan: update tasklist setiap selesai satu task/grup task sebelum melapor.

**Progress: 100%**

## 1. Project Setup & Configuration

- [x] ✅ Task 1.1 — Install `mews/purifier` (composer require), publikasi & konfigurasi config purifier (strip semua HTML, batas panjang). `[Mudah]` (Selesai)
  - `composer require mews/purifier`; `php artisan vendor:publish --provider=Mews\Purifier\PurifierServiceProvider`; `config/purifier.php` → `HTML.Allowed=''`, AutoParagraph false.
- [x] ✅ Task 1.2 — Install `axios` via npm (AJAX polling per PRD). `[Mudah]` (Selesai)
  - `npm install axios` → ditambahkan ke `package.json`.
- [x] ✅ Task 1.3 — Setup design tokens Tailwind v4 (`@theme`) di `resources/css/app.css`: warna neutral/surface/primary/secondary/tertiary sesuai `design.md`, font JetBrains Mono + Inter. `[Mudah]` (Selesai)
  - `resources/css/app.css` (@theme + base layar); `vite.config.js` fonts → JetBrains Mono (400,500) + Inter (400,500,600).
- [x] ✅ Task 1.4 — Daftarkan middleware `room.expired` (CheckRoomExpired) dan rate limiter `chat` (1 msg/detik/user) di `bootstrap/app.php` / AppServiceProvider. `[Sedang]` (Selesai)
  - `app/Http/Middleware/CheckRoomExpired.php` baru; alias di `bootstrap/app.php`; `RateLimiter::for('chat', Limit::perSecond(1))` di `app/Providers/AppServiceProvider.php`.

## 2. Database & Migration

- [x] ✅ Task 2.1 — Rewrite migration users → tabel `rooms` + `users` (peserta chat) + pertahankan `sessions`; drop kolom auth & `password_reset_tokens`. `[Sedang]` (Selesai)
  - Diganti nama → `database/migrations/0001_01_01_000000_create_rooms_and_users_tables.php`.
- [x] ✅ Task 2.2 — Migration baru `messages` (room_id, user_id nullable, is_system, message; index room_id+created_at). `[Sedang]` (Selesai)
  - `database/migrations/2026_08_29_000000_create_messages_table.php`.
- [x] ✅ Task 2.3 — `php artisan migrate:fresh` pada DB MySQL lokal. `[Mudah]` (Selesai)
  - Semua 4 migration terverifikasi jalan.

## 3. Models & Relationships

- [x] ✅ Task 3.1 — Model `App\Models\Room`. `[Mudah]` (Selesai)
  - `app/Models/Room.php` + `database/factories/RoomFactory.php`.
- [x] ✅ Task 3.2 — Repurpose `App\Models\User` jadi peserta chat; rewrite `UserFactory`. `[Sedang]` (Selesai)
  - `app/Models/User.php` (tanpa Authenticatable), `database/factories/UserFactory.php`.
- [x] ✅ Task 3.3 — Model `App\Models\Message`. `[Mudah]` (Selesai)
  - `app/Models/Message.php` + `database/factories/MessageFactory.php`.

## 4. Backend Logic & API

- [x] ✅ Task 4.1 — `RoomController@index` & `@store` (create room: code 8 char collision-resistant retry, expired_at = now+24 jam, session, redirect). `[Sedang]` (Selesai)
  - `app/Services/RoomService.php` (createRoom/generateCode), `app/Http/Controllers/RoomController.php`.
- [x] ✅ Task 4.2 — `RoomController@show` & `@join` (simpan nama session; cegah duplikat suffix "Rafif (2)"). `[Sedang]` (Selesai)
  - `uniqueName()` di RoomService.
- [x] ✅ Task 4.3 — `ChatController@index` (GET incremental `after` id) & `@store` (validasi, clean, last_seen). `[Sedang]` (Selesai)
  - `app/Http/Controllers/ChatController.php`.
- [x] ✅ Task 4.4 — `ChatController@typing` (POST typing_at=now) & `@typingStatus` (GET read-only). `[Sedang]` (Selesai)
- [x] ✅ Task 4.5 — `ChatController@presence` (online last_seen>=30s) & `@leave` (system message, offline, clear session). `[Sedang]` (Selesai)
- [x] ✅ Task 4.6 — Routes web semua endpoint (CSRF default web middleware). `[Mudah]` (Selesai)
  - `routes/web.php`; binding room-by-code; throttle:chat di POST messages.

## 5. Security (Input Sanitization, Rate Limiting, XSS, CSRF)

- [x] ✅ Task 5.1 — `mews/purifier` strip semua HTML; `clean()` diterapkan ke nama & pesan. `[Mudah]` (Selesai)
  - `config/purifier.php` (HTML.Allowed=''); `RoomService::clean()`.
- [x] ✅ Task 5.2 — Validasi: nama max 30 char, pesan max 500 char (required). `[Mudah]` (Selesai)
  - Rules di `RoomController@store/join`, `ChatController@store`.
- [x] ✅ Task 5.3 — Rate limiter `chat` (1 pesan/detik/user, per PRD) di route POST message → 429. `[Sedang]` (Selesai)
  - `AppServiceProvider::boot()` (`Limit::perSecond(1)` keyed user-room dari session) + `throttle:chat` di `routes/web.php`. Key tidak bergantung session id/binding karena throttle jalan sebelum SubstituteBindings.
- [x] ✅ Task 5.4 — Middleware `CheckRoomExpired` (404 saat expired) di semua route `/room/{code}`. `[Sedang]` (Selesai)
  - `app/Http/Middleware/CheckRoomExpired.php` (resolve by code + cek isExpired).

## 6. Frontend & Blade Views (Ikuti design.md)

- [x] ✅ Task 6.1 — `layouts/app.blade.php` (dark neutral, mono display + Inter, @vite, container toast). `[Sedang]` (Selesai)
  - `resources/views/layouts/app.blade.php`.
- [x] ✅ Task 6.2 — `home.blade.php` (input nama + tombol Create Room, single-accent). `[Mudah]` (Selesai)
- [x] ✅ Task 6.3 — `room.blade.php` (header kode room + copy + WA/TG + leave; online count; countdown; area pesan; input; form join bila belum daftar). `[Sedang]` (Selesai)
- [x] ✅ Task 6.4 — Bubble chat sesuai design.md (own tertiary kanan, other surface kiri, timestamp HH:mm, empty state, system message); animasi typing-dots & message-enter di `app.css`. `[Sedang]` (Selesai)

## 7. JavaScript & Realtime Features

- [x] ✅ Task 7.1 — Polling loop 2 detik (axios): fetch pesan incremental + presence + typing status tanpa reload. `[Sedang]` (Selesai)
  - `resources/js/room.js` (`poll()` tiap 2s); `resources/js/app.js` entry.
- [x] ✅ Task 7.2 — True debounce 300ms POST typing; tampil "X sedang mengetik..." + animasi titik; hilang ±3s (window 3s server). `[Sedang]` (Selesai)
- [x] ✅ Task 7.3 — Smart auto-scroll (tetap bawah saat dekat) + countdown realtime (merah <1 jam). `[Sedang]` (Selesai)

## 8. Additional Features (Sound, Share, Toast, System Message)

- [x] ✅ Task 8.1 — Tombol Copy Link + toast success/error/info. `[Mudah]` (Selesai)
  - `resources/js/helpers.js` (toast, copyText).
- [x] ✅ Task 8.2 — Share via WhatsApp & Telegram. `[Mudah]` (Selesai)
- [x] ✅ Task 8.3 — Notifikasi suara pesan baru (WebAudio beep) + toggle on/off (localStorage). `[Sedang]` (Selesai)
- [x] ✅ Task 8.4 — Leave Room dengan konfirmasi + system message "joined/left the room". `[Mudah]` (Selesai)
  - Backend `RoomController@join` & `ChatController@leave` via RoomService.

## 9. Cleanup Command & Scheduler

- [x] ✅ Task 9.1 — Command `app/Console/Commands/CleanupExpiredRooms.php` (`rooms:cleanup`) hapus room expired (cascade users & messages). `[Sedang]` (Selesai)
  - `php artisan rooms:cleanup` terverifikasi (`Deleted 0 expired room(s).`).
- [x] ✅ Task 9.2 — Daftarkan scheduler `Schedule::command('rooms:cleanup')->everyMinute()` di `routes/console.php`. `[Mudah]` (Selesai)
  - `routes/console.php`; jalankan lewat `php artisan schedule:work` (dev) / cron tiap menit (prod).

## 10. Final Polish & Bug Fixes

- [x] ✅ Task 10.1 — Feature test: homepage, create room (code 8 char, expiry 24 jam, unik), join + dedup nama, expired 404, validasi + sanitasi XSS, throttle 429, typing read-only, presence online, leave + system message, command cleanup. `[Sedang]` (Selesai)
  - `tests/Feature/HomePageTest.php`, `RoomTest.php`, `ChatTest.php`, `CleanupRoomTest.php` — **24 test, 71 assertions, semua hijau** (`composer test`). Rate-limit test diverifikasi: throttle jalan sebelum SubstituteBindings & session id bisa berganti → limiter resolve room by code + key `room_user_{id}`.
- [x] ✅ Task 10.2 — `vendor/bin/pint` format, `npm run build`, smoke test via `composer dev`. `[Mudah]` (Selesai)
  - `vendor/bin/pint` memformat 21 file; `npm run build` OK (assets `public/build/`); `composer test` tetap hijau pasca-format.
- [x] ✅ Task 10.3 — Instruksi menjalankan (migrate, npm install, scheduler `php artisan schedule:work` / cron, demo). `[Mudah]` (Selesai)
  - Ditulis di `README.md` (setup, migrate, asset build, scheduler dev/prod, alur demo, daftar command).

---
*Format update wajib: tandai `[x]` + emoji ✅ + catatan file yang diubah setelah tiap task selesai.*