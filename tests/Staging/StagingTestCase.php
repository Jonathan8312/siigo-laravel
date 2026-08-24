<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Staging;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;

/**
 * Base class for tests that hit the real Siigo sandbox API. Opt-in only:
 * every test skips (never fails) when .env.staging.local is missing or
 * incomplete, and this suite is never run in CI (see
 * .github/workflows/ci.yml). Run locally with `composer test:staging`.
 *
 * Deliberately extends plain PHPUnit\Framework\TestCase, not the
 * package's Testbench-based TestCase — these tests talk to Siigo
 * directly through the same classes a consuming application would use,
 * without needing a full Laravel container.
 */
abstract class StagingTestCase extends TestCase
{
    /**
     * @var array<string, string>
     */
    private static array $env = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $path = dirname(__DIR__, 2);

        if (! is_file($path.'/.env.staging.local')) {
            return;
        }

        self::$env = array_filter(
            Dotenv::createImmutable($path, '.env.staging.local')->safeLoad(),
            is_string(...),
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (self::env('SIIGO_USERNAME') === null || self::env('SIIGO_ACCESS_KEY') === null) {
            $this->markTestSkipped(
                'No Siigo sandbox credentials found in .env.staging.local — skipping staging test.'
            );
        }
    }

    protected static function env(string $key): ?string
    {
        $value = self::$env[$key] ?? $_ENV[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    protected static function envOrDefault(string $key, string $default): string
    {
        return self::env($key) ?? $default;
    }
}
