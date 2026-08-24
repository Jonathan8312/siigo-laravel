<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Feature;

use Jonathan8312\Siigo\Siigo;
use Jonathan8312\Siigo\Tests\TestCase;

final class SiigoServiceProviderTest extends TestCase
{
    public function test_siigo_resolves_as_a_singleton(): void
    {
        $first = $this->app()->make(Siigo::class);
        $second = $this->app()->make(Siigo::class);

        $this->assertSame($first, $second);
    }

    public function test_siigo_alias_resolves_to_the_same_singleton(): void
    {
        $this->assertSame($this->app()->make(Siigo::class), $this->app()->make('siigo'));
    }

    public function test_config_is_merged_with_documented_defaults(): void
    {
        // base_url/partner_id are overridden by this suite's TestCase for
        // predictable fake HTTP hosts; assert on defaults left untouched.
        $this->assertSame(5, config('siigo.connect_timeout'));
        $this->assertSame(15, config('siigo.timeout'));
        $this->assertFalse(config('siigo.retry.enabled'));
        $this->assertSame(20_000_000, config('siigo.max_response_bytes'));
    }

    public function test_config_file_is_publishable(): void
    {
        $this->assertFileExists(__DIR__.'/../../config/siigo.php');
    }

    public function test_an_invalid_partner_id_fails_fast_at_resolution_time(): void
    {
        config()->set('siigo.partner_id', 'invalid-partner-id');
        $this->app()->forgetInstance(Siigo::class);

        $this->expectException(\InvalidArgumentException::class);

        $this->app()->make(Siigo::class);
    }
}
