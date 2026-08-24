<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\Auth;

use Jonathan8312\Siigo\Auth\AccessToken;
use Jonathan8312\Siigo\Exceptions\AuthenticationException;
use PHPUnit\Framework\TestCase;

final class AccessTokenTest extends TestCase
{
    public function test_from_auth_response_computes_expiry_from_issued_at_plus_expires_in(): void
    {
        $issuedAt = new \DateTimeImmutable('2026-08-23T00:00:00+00:00');

        $token = AccessToken::fromAuthResponse([
            'access_token' => 'jwt-value',
            'expires_in' => 86400,
        ], $issuedAt);

        $this->assertSame('jwt-value', $token->value());
        $this->assertSame('2026-08-24T00:00:00+00:00', $token->expiresAt()->format(\DATE_ATOM));
    }

    public function test_from_auth_response_rejects_a_missing_access_token(): void
    {
        $this->expectException(AuthenticationException::class);

        AccessToken::fromAuthResponse(['expires_in' => 86400], new \DateTimeImmutable);
    }

    public function test_from_auth_response_rejects_a_missing_expires_in(): void
    {
        $this->expectException(AuthenticationException::class);

        AccessToken::fromAuthResponse(['access_token' => 'jwt-value'], new \DateTimeImmutable);
    }

    public function test_is_valid_respects_the_safety_margin(): void
    {
        $expiresAt = new \DateTimeImmutable('2026-08-23T12:00:00+00:00');
        $token = new AccessToken('jwt-value', $expiresAt);

        $wellBeforeExpiry = new \DateTimeImmutable('2026-08-23T11:00:00+00:00');
        $withinSafetyMargin = new \DateTimeImmutable('2026-08-23T11:59:30+00:00');
        $afterExpiry = new \DateTimeImmutable('2026-08-23T12:00:01+00:00');

        $this->assertTrue($token->isValid($wellBeforeExpiry, 60));
        $this->assertFalse($token->isValid($withinSafetyMargin, 60));
        $this->assertFalse($token->isValid($afterExpiry, 60));
    }

    public function test_debug_info_never_exposes_the_raw_token(): void
    {
        $token = new AccessToken('super-secret-jwt', new \DateTimeImmutable('+1 day'));

        $dump = print_r($token, true);

        $this->assertStringNotContainsString('super-secret-jwt', $dump);
        $this->assertStringContainsString('[REDACTED]', $dump);
    }

    public function test_cannot_be_serialized(): void
    {
        $this->expectException(\LogicException::class);

        serialize(new AccessToken('jwt-value', new \DateTimeImmutable('+1 day')));
    }
}
