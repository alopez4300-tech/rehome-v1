# Rehome – Light Profile (Default)

A small, internal-first Laravel 11 + Filament v3 admin app. We keep the hardened foundation you already built, but **run light by default**—no realtime, no Redis/Horizon, no cost tracking—until the business needs it.

## Profiles
- `APP_PROFILE=light` (default): simplest local/prod operation. No heavy providers.
- `APP_PROFILE=scale`: when you actually need realtime, Horizon/Redis, cost tracking, etc. Turn on later.

## Light Defaults
Set these in `.env` (and keep them in `.env.example`):
```dotenv
APP_PROFILE=light
QUEUE_CONNECTION=sync
CACHE_DRIVER=file
BROADCAST_DRIVER=log
SESSION_DRIVER=file
MAIL_MAILER=log
```

Minimal toggle (only when scaling later):
```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    $scale = env('APP_PROFILE', 'light') === 'scale';
    if ($scale) {
        // $this->app->register(\Laravel\Horizon\HorizonServiceProvider::class);
        // $this->app->register(\App\Providers\RealtimeServiceProvider::class);
        // $this->app->register(\App\Providers\CostTrackingServiceProvider::class);
    }
}
```

## What’s In / Out (for now)
**In:** Filament admin at `/admin`, resources for **Users**, **Projects**, **Tasks**; simple dashboard widget; soft-deletes on Projects; green tests.

**Out (until scale):** Realtime/WebSockets, Horizon/Redis, cost tracking, multi-portal UIs, complex multi-workspace UI.

## Getting Started (Light)
```bash
# 1) Install deps
cd backend
composer install
cp .env.example .env
php artisan key:generate

# 2) Ensure light defaults in .env (see above)

# 3) Migrate + seed (optional admin seeder if present)
php artisan migrate --seed

# 4) Run
php artisan serve  # or your Docker/Sail command

# 5) Tests
php artisan test
```

## Filament MVP
- Resources: **User**, **Project**, **Task** (List/Create/Edit).
- Policies: Admins manage all; members read-only (optional).
- Dashboard (Blade): 3 stat cards (Projects, Users, Open Tasks) + Recent Projects table.

## Contributing Guidelines
- Keep PRs small and user-facing: CRUD first, heavy features later.
- No new heavy dependencies under `light` profile.
- Tests: add/keep fast smoke tests; avoid heavy suites.

## Scaling Later (When Needed)
1. Flip `APP_PROFILE=scale`.
2. Switch drivers: `QUEUE_CONNECTION=redis`, set up Redis + Horizon; choose a broadcast driver (Reverb/Pusher).
3. Register heavy providers in `AppServiceProvider` block.
4. Add targeted tests for the new capabilities.

---
**TL;DR**: Ship small now; the foundation can scale when you actually need it.
