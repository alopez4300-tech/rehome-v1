<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# Rehome v1 Backend

A multi-tenant project management platform built with Laravel 11, featuring a 3-surface architecture and flexible operational profiles.

## 🏗️ Architecture Overview

### 3-Surface Architecture

- **System Admin** (`/admin`) - Global system administration (Filament)
- **Workspace Admin** (`/ops`) - Workspace-scoped operations (Filament)
- **App SPA** (`/app`) - Project-focused user interface (React/Vue)

### Authentication System

- **Laravel Sanctum** SPA authentication with smart redirects
- **Spatie Laravel Permission** for role-based access control
- **Multi-workspace support** with flexible user memberships
- **Cross-database compatibility** (SQLite, MySQL, PostgreSQL)

## 🚀 Operational Profiles

The application supports two operational profiles via the `APP_PROFILE` environment variable:

### Light Profile (Default)

Minimal overhead configuration perfect for development and small deployments:

```bash
make light  # Switch to light profile
```

**Configuration:**

- **Database:** SQLite (single file)
- **Queue:** Sync (immediate processing)
- **Cache:** File-based
- **Broadcasting:** Log-based
- **Features:** Essential features only

**Use Cases:** Development, small teams, proof-of-concept deployments

### Scale Profile

Production-optimized configuration with full feature set:

```bash
make scale  # Switch to scale profile
```

**Configuration:**

- **Database:** MySQL/PostgreSQL with connection pooling
- **Queue:** Redis-backed async processing
- **Cache:** Redis with distributed caching
- **Broadcasting:** Real-time with Pusher/WebSocket
- **Features:** All features enabled including cost tracking, real-time updates

**Use Cases:** Production, high-traffic environments, enterprise deployments

## 🎛️ Feature Flag System

Dynamic feature control via environment variables:

```php
// Check if a feature is enabled
if (feature('cost_tracking')) {
    // Cost tracking logic
}

// Get current profile
$currentProfile = profile(); // 'light' or 'scale'

// Check specific profile
if (profile('scale')) {
    // Scale-specific logic
}
```

### Available Feature Flags

| Feature                 | Light   | Scale  | Description                         |
| ----------------------- | ------- | ------ | ----------------------------------- |
| `FEATURE_COST_TRACKING` | `false` | `true` | Project cost analysis and budgeting |
| `FEATURE_REALTIME`      | `false` | `true` | Real-time updates and notifications |
| `FEATURE_MULTI_TENANT`  | `true`  | `true` | Workspace isolation and scoping     |
| `FEATURE_CLIENT_PORTAL` | `false` | `true` | External client access portal       |

### Zero-Overhead Design

Features use the **null object pattern** - when disabled, they consume zero resources:

```php
// In FeatureServiceProvider
if (feature('cost_tracking')) {
    $this->app->bind(CostMeter::class, DatabaseCostMeter::class);
} else {
    $this->app->bind(CostMeter::class, NullCostMeter::class); // No-op implementation
}
```

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
