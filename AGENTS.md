# TempChat (Laravel 13) — temporary chat room

Laravel 13 app: users create shareable chat rooms (8-char code) that expire after 24h. No login/registration; realtime via 2s AJAX polling. **Feature-complete and tested** — this is not a stock Laravel install. Spec & progress live in `.agents/` (tasklist is at 100%).

## Project Spec (read before coding)

- `.agents/prd.md` — full product requirements (Indonesian)
- `.agents/design.md` — design system to follow strictly for UI (colors, typography, components)
- `.agents/task-instruction.md` — mandatory rule: update `.agents/tasklist.md` (mark `[x]` + ✅ + note changed files) after every task, before reporting

## Commands

```sh
composer setup      # full bootstrap: install, .env, key, migrate, npm build
composer dev        # php artisan dev: serve http://localhost:8000 + Vite hot reload
composer test       # config:clear + php artisan test — SQLite in-memory, no DB server needed
vendor/bin/phpunit  # direct runner
php artisan test --filter ChatTest    # single test file
vendor/bin/pint     # Laravel Pint formatter
php artisan rooms:cleanup             # manual expired-room cleanup (scheduler runs it every min)
php artisan schedule:work             # run scheduler in dev
```

## Testing

- Runner is **PHPUnit** — Pest is NOT installed (only a pest plugin is allowed in composer.json; no `vendor/bin/pest`).
- `phpunit.xml` forces SQLite in-memory, so tests never touch the MySQL in `.env`.
- Feature tests: `tests/Feature/{HomePageTest,RoomTest,ChatTest,CleanupRoomTest,ExampleTest}.php` — 24 tests / 71 assertions.

## Architecture (non-obvious)

- `App\Models\User` is **not an auth user** — it's a chat participant (plain Model, no Authenticatable, no password). Don't bolt Laravel auth onto it.
- Rooms are bound by the `code` column, not id (`Route::bind` in `routes/web.php`). `app/Http/Middleware/CheckRoomExpired.php` re-resolves the route param and aborts 404 when the room is expired (24h).
- Identity is session-based, not a login system: session key `room_user_{room_id}` stores the participant id. `app/Services/RoomService.php` owns create/join/uniqueName/leave/system-message logic.
- All text input is sanitized server-side via `mews/purifier` (`config/purifier.php` strips all HTML); `RoomService::clean()` is the single entrypoint. UI copy is Indonesian, system messages are English.
- No WebSocket/Broadcast: realtime = 2s axios polling in `resources/js/room.js` (incremental messages via `after` id, typing, presence, auto-scroll).
- Message POSTs are rate-limited 1/sec/user/room via `throttle:chat` (defined in `AppServiceProvider::boot()`). The limiter runs before route binding, so it resolves the room by code string itself.
- Cleanup: `routes/console.php` schedules `rooms:cleanup` every minute; rooms destroy users + messages by cascade.

## Env gotchas

- Local `.env` uses MySQL; committed `.env.example` defaults to SQLite. Tests bypass both. A `database/database.sqlite` file exists for SQLite setups.
- `node_modules` and `public/build` are not committed — run `npm install` before any Vite command.
- `public/hot` is a stale Vite-dev pointer (`http://127.0.0.1:5173`): while present, Laravel serves assets from the Vite dev server, so a plain `php artisan serve` renders pages without CSS. Delete it or run `npm run dev`.
- PHPStan is NOT installed — don't assume it exists.
- `CLAUDE.md` is generic Laravel-Boost boilerplate from `laravel new` (it suggests installing `laravel/boost`) — ignore it for this repo.

## Frontend Stack

Tailwind v4 via `@tailwindcss/vite` — no `tailwind.config.js`; theme in `resources/css/app.css` (`@theme`) plus `vite.config.js` fonts (JetBrains Mono + Inter). Entrypoints: `resources/css/app.css`, `resources/js/app.js` (loads `home.js`, `room.js`, `helpers.js`).