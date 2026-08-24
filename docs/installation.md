# Installation

## Requirements

- PHP 8.2+
- Laravel 12 or 13

## Install

```bash
composer require jonathan8312/siigo-laravel
```

Laravel's package auto-discovery registers `Jonathan8312\Siigo\SiigoServiceProvider`
automatically — no manual registration is needed.

## Publish the configuration

```bash
php artisan vendor:publish --tag=siigo-config
```

This creates `config/siigo.php` in your application. Publishing is optional: the package
works out of the box with sensible defaults (see [configuration.md](configuration.md)) and
reads everything from environment variables either way.

## Set your credentials

Add to your `.env`:

```env
SIIGO_USERNAME=
SIIGO_ACCESS_KEY=
SIIGO_PARTNER_ID=YourCompanyName
```

`SIIGO_USERNAME` and `SIIGO_ACCESS_KEY` come from Siigo Nube, under **Alianzas → Mi
Credencial API**. See [authentication.md](authentication.md) for details on how the SDK uses
them, and [configuration.md](configuration.md) for `SIIGO_PARTNER_ID` and every other option.

## Verify it works

```php
use Jonathan8312\Siigo\Siigo;

$siigo = app(Siigo::class);
```

Resolving `Siigo::class` from the container should not throw. If your configuration is
invalid (e.g. an empty base URL or a malformed `SIIGO_PARTNER_ID`), the SDK fails fast with a
clear `InvalidArgumentException` at resolution time rather than a confusing error later.
