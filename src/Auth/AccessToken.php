<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Auth;

use Jonathan8312\Siigo\Exceptions\AuthenticationException;
use SensitiveParameter;

/**
 * A Siigo JWT access token, paired with the moment it expires.
 *
 * Deliberately not serializable — the token is boxed inside a private
 * {@see \Closure}, the same defensive pattern used by
 * {@see AuthCredentials}, so it can never be accidentally dumped or
 * persisted anywhere except through {@see CacheTokenRepository}, which
 * extracts {@see self::value()} and {@see self::expiresAt()} explicitly
 * rather than serializing this object directly.
 */
final class AccessToken
{
    /** @var \Closure(): string */
    private readonly \Closure $tokenBox;

    public function __construct(
        #[SensitiveParameter] string $token,
        private readonly \DateTimeImmutable $expiresAt,
    ) {
        $this->tokenBox = static fn (): string => $token;
    }

    /**
     * Build an AccessToken from a successful POST /auth response body.
     *
     * @param  array<array-key, mixed>  $data
     */
    public static function fromAuthResponse(array $data, \DateTimeImmutable $issuedAt): self
    {
        $token = $data['access_token'] ?? null;
        $expiresIn = $data['expires_in'] ?? null;

        if (! is_string($token) || $token === '' || ! is_numeric($expiresIn)) {
            throw new AuthenticationException(
                'Siigo returned an unexpected response shape for POST /auth (missing or invalid access_token/expires_in).',
                endpoint: 'POST /auth',
            );
        }

        return new self($token, $issuedAt->modify('+'.((int) $expiresIn).' seconds'));
    }

    public function value(): string
    {
        return ($this->tokenBox)();
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * Whether this token is still safe to use, applying a safety margin
     * so a request never races against the token expiring mid-flight.
     */
    public function isValid(\DateTimeImmutable $now, int $safetyMarginSeconds): bool
    {
        return $now->modify('+'.max(0, $safetyMarginSeconds).' seconds') < $this->expiresAt;
    }

    /**
     * @return array{token: string, expiresAt: string}
     */
    public function __debugInfo(): array
    {
        return [
            'token' => '[REDACTED]',
            'expiresAt' => $this->expiresAt->format(\DATE_ATOM),
        ];
    }

    public function __serialize(): never
    {
        throw new \LogicException(self::class.' must not be serialized directly; see CacheTokenRepository.');
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public function __unserialize(array $data): never
    {
        throw new \LogicException(self::class.' must not be unserialized directly; see CacheTokenRepository.');
    }
}
