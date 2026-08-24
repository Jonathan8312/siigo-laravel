# Siigo Laravel

[![CI](https://github.com/jonathan8312/siigo-laravel/actions/workflows/ci.yml/badge.svg)](https://github.com/jonathan8312/siigo-laravel/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/jonathan8312/siigo-laravel.svg)](https://packagist.org/packages/jonathan8312/siigo-laravel)
[![License](https://img.shields.io/packagist/l/jonathan8312/siigo-laravel.svg)](LICENSE)

A community Laravel SDK for the public [Siigo API](https://developers.siigo.com/docs/siigoapi) (Colombia). Not an official Siigo package.

## What it does

- Authenticates against Siigo (JWT), caching and renewing the token automatically.
- Provides a single, centralized HTTP client with consistent error handling, retries, and Partner-Id/Idempotency-Key support.
- Maps Siigo's error responses to a typed exception hierarchy.

## What it does not do

- No Eloquent models, no local database tables.
- No business logic: it does not calculate taxes, totals, or make fiscal decisions. Your application owns that; this SDK owns talking to Siigo correctly and safely.

## Status

This package is under active, phased development (see [CLAUDE.md](CLAUDE.md) for the roadmap). The current release covers the **Core** (bootstrap, configuration, authentication, HTTP client) and **Catalogs** (read-only reference data). Business resources (Customers, Products, Invoices, ...) are implemented phase by phase.

## Requirements

- PHP 8.2+
- Laravel 12 or 13

## Installation

```bash
composer require jonathan8312/siigo-laravel
```

```bash
php artisan vendor:publish --tag=siigo-config
```

See [docs/installation.md](docs/installation.md) for details.

## Configuration

```env
SIIGO_USERNAME=
SIIGO_ACCESS_KEY=
SIIGO_PARTNER_ID=YourCompanyName
```

See [docs/configuration.md](docs/configuration.md) for every available option.

## Usage

```php
use Jonathan8312\Siigo\Siigo;

$siigo = app(Siigo::class);

// Or via dependency injection:
public function __construct(private Siigo $siigo) {}

$taxes = $siigo->catalogs()->taxes();
```

Business resource methods (`$siigo->customers()`, `$siigo->products()`, ...) are added as each module ships — see [docs/known-issues.md](docs/known-issues.md) and [docs/research/siigo-api-co](docs/research/siigo-api-co) for what has already been investigated against the real Siigo API.

## Documentation

- [Installation](docs/installation.md)
- [Configuration](docs/configuration.md)
- [Authentication](docs/authentication.md)
- [Catalogs](docs/catalogs.md)
- [Errors](docs/errors.md)
- [Testing](docs/testing.md)
- [Known issues](docs/known-issues.md)

## License

MIT. See [LICENSE](LICENSE).
