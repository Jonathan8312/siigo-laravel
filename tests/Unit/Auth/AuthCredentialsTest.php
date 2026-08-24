<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\Auth;

use Jonathan8312\Siigo\Auth\AuthCredentials;
use PHPUnit\Framework\TestCase;

final class AuthCredentialsTest extends TestCase
{
    public function test_has_no_credentials_by_default(): void
    {
        $credentials = new AuthCredentials;

        $this->assertFalse($credentials->hasCredentials());
        $this->assertNull($credentials->username());
        $this->assertNull($credentials->accessKey());
    }

    public function test_exposes_the_configured_pair(): void
    {
        $credentials = new AuthCredentials('user@example.com', 'secret-key');

        $this->assertTrue($credentials->hasCredentials());
        $this->assertSame('user@example.com', $credentials->username());
        $this->assertSame('secret-key', $credentials->accessKey());
    }

    public function test_with_credentials_returns_a_new_detached_instance(): void
    {
        $original = new AuthCredentials('user@example.com', 'secret-key');
        $derived = $original->withCredentials('other@example.com', 'other-key');

        $this->assertNotSame($original, $derived);
        $this->assertSame('user@example.com', $original->username());
        $this->assertSame('other@example.com', $derived->username());
    }

    public function test_fingerprint_is_stable_for_the_same_pair(): void
    {
        $a = new AuthCredentials('user@example.com', 'secret-key');
        $b = new AuthCredentials('user@example.com', 'secret-key');

        $this->assertSame($a->fingerprint(), $b->fingerprint());
    }

    public function test_fingerprint_differs_when_either_value_changes(): void
    {
        $base = new AuthCredentials('user@example.com', 'secret-key');
        $differentUser = new AuthCredentials('other@example.com', 'secret-key');
        $differentKey = new AuthCredentials('user@example.com', 'other-key');

        $this->assertNotSame($base->fingerprint(), $differentUser->fingerprint());
        $this->assertNotSame($base->fingerprint(), $differentKey->fingerprint());
    }

    public function test_fingerprint_throws_without_credentials(): void
    {
        $this->expectException(\LogicException::class);

        (new AuthCredentials)->fingerprint();
    }

    public function test_debug_info_never_exposes_raw_values(): void
    {
        $credentials = new AuthCredentials('user@example.com', 'secret-key');

        $dump = print_r($credentials, true);

        $this->assertStringNotContainsString('user@example.com', $dump);
        $this->assertStringNotContainsString('secret-key', $dump);
        $this->assertStringContainsString('[REDACTED]', $dump);
    }

    public function test_cannot_be_serialized(): void
    {
        $this->expectException(\LogicException::class);

        serialize(new AuthCredentials('user@example.com', 'secret-key'));
    }
}
