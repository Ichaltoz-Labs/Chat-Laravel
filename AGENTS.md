# JamSholat (Laravel 13)

This is a fresh Laravel 13 app — no application code has been built yet (stock `welcome` route, only the default `User` model and migrations). The real project spec lives in `.agents/`.

## Project Spec (read before coding)

- `.agents/prd.md` — full product requirements (temporary chat room app, in Indonesian)
- `.agents/design.md` — design system (colors, typography, components) to follow strictly for UI
- `.agents/task-instruction.md` — task breakdown conventions and mandatory tasklist updates

## Environment Setup

```sh
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate
```

Note: the local `.env` uses MySQL (`DB_CONNECTION=mysql`), but the committed `.env.example` defaults to SQLite. `node_modules` is not committed — run `npm install` before any Vite command, otherwise `npm run dev` / `npm run build` will fail.

## Development

```sh
composer dev        # runs php artisan dev (Laravel 13 built-in: serve + Vite)
```

## Testing

```sh
composer test       # artisan config:clear then php artisan test
vendor/bin/phpunit  # direct PHPUnit runner
```

- The runner is **PHPUnit** — Pest is NOT installed (a pest-plugin is merely allowed in composer.json, no `vendor/bin/pest` binary exists).
- `phpunit.xml` forces **SQLite in-memory** (`DB_DATABASE=:memory:`) regardless of your `.env` MySQL config — no DB server needed for tests.
- Run a single test with `php artisan test --filter <name>`.

## Code Quality

```sh
vendor/bin/pint     # Laravel Pint formatter (installed)
```

PHPStan is NOT installed — do not assume it exists.

## Frontend Stack

Tailwind CSS v4 via the `@tailwindcss/vite` plugin — no `tailwind.config.js`; configure theme in CSS. Entrypoints: `resources/css/app.css`, `resources/js/app.js`.