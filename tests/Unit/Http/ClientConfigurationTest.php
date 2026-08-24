<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\Http;

use Jonathan8312\Siigo\Http\ClientConfiguration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ClientConfigurationTest extends TestCase
{
    public function test_accepts_a_valid_configuration(): void
    {
        $config = new ClientConfiguration(
            baseUrl: 'https://api.siigo.com',
            partnerId: 'TREBOLDEV',
            connectTimeout: 5.0,
            timeout: 15.0,
        );

        $this->assertSame('https://api.siigo.com', $config->baseUrl);
        $this->assertSame('TREBOLDEV', $config->partnerId);
    }

    public function test_rejects_an_empty_base_url(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ClientConfiguration(baseUrl: '', partnerId: 'TREBOLDEV', connectTimeout: 5.0, timeout: 15.0);
    }

    public function test_rejects_a_non_https_base_url(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ClientConfiguration(baseUrl: 'http://api.siigo.com', partnerId: 'TREBOLDEV', connectTimeout: 5.0, timeout: 15.0);
    }

    #[DataProvider('invalidPartnerIds')]
    public function test_rejects_an_invalid_partner_id(string $partnerId): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ClientConfiguration(baseUrl: 'https://api.siigo.com', partnerId: $partnerId, connectTimeout: 5.0, timeout: 15.0);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidPartnerIds(): array
    {
        return [
            'empty' => [''],
            // Confirmed empirically against Siigo's real API: a hyphen is
            // rejected even though it "looks" like a harmless separator.
            'contains a hyphen' => ['siigo-laravel-sdk'],
            'contains a space' => ['siigo laravel'],
            'too short' => ['ab'],
            'contains a dot' => ['trebol.dev'],
        ];
    }

    public function test_rejects_non_positive_timeouts(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ClientConfiguration(baseUrl: 'https://api.siigo.com', partnerId: 'TREBOLDEV', connectTimeout: 0.0, timeout: 15.0);
    }

    public function test_rejects_a_retry_max_attempts_below_one(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ClientConfiguration(
            baseUrl: 'https://api.siigo.com',
            partnerId: 'TREBOLDEV',
            connectTimeout: 5.0,
            timeout: 15.0,
            retryMaxAttempts: 0,
        );
    }

    public function test_rejects_a_negative_retry_backoff(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ClientConfiguration(
            baseUrl: 'https://api.siigo.com',
            partnerId: 'TREBOLDEV',
            connectTimeout: 5.0,
            timeout: 15.0,
            retryBackoffMilliseconds: -1,
        );
    }

    public function test_rejects_a_max_response_bytes_below_one(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ClientConfiguration(
            baseUrl: 'https://api.siigo.com',
            partnerId: 'TREBOLDEV',
            connectTimeout: 5.0,
            timeout: 15.0,
            maxResponseBytes: 0,
        );
    }

    public function test_rejects_a_negative_token_safety_margin(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ClientConfiguration(
            baseUrl: 'https://api.siigo.com',
            partnerId: 'TREBOLDEV',
            connectTimeout: 5.0,
            timeout: 15.0,
            tokenSafetyMarginSeconds: -1,
        );
    }
}
