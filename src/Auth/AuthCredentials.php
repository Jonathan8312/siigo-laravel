<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Auth;

use Jonathan8312\Siigo\Http\ClientConfiguration;
use SensitiveParameter;

/**
 * The username/access_key pair Siigo exchanges for a JWT via POST /auth.
 *
 * Immutable and deliberately minimal: it carries only the login material,
 * not the resulting token (see {@see AccessToken}) or any non-secret
 * transport configuration (see {@see ClientConfiguration}).
 *
 * Neither value is exposed as a public property, and this class never
 * appears in a form that could accidentally leak them: it cannot be
 * serialized, and var_dump()/print_r()/var_export() only ever show a
 * redacted placeholder. Both values are stored inside private
 * {@see \Closure} instances rather than as plain string properties —
 * var_export() has no magic-method hook to override its output (unlike
 * var_dump()/print_r(), which respect {@see self::__debugInfo()}), but
 * it also cannot introspect a Closure's captured variables.
 */
final class AuthCredentials
{
    /** @var (\Closure(): string)|null */
    private readonly ?\Closure $usernameBox;

    /** @var (\Closure(): string)|null */
    private readonly ?\Closure $accessKeyBox;

    public function __construct(
        #[SensitiveParameter] ?string $username = null,
        #[SensitiveParameter] ?string $accessKey = null,
    ) {
        $this->usernameBox = $username !== null && $username !== '' ? static fn (): string => $username : null;
        $this->accessKeyBox = $accessKey !== null && $accessKey !== '' ? static fn (): string => $accessKey : null;
    }

    /**
     * Return a new, detached AuthCredentials instance carrying the given
     * pair. The original instance is never modified.
     */
    public function withCredentials(
        #[SensitiveParameter] string $username,
        #[SensitiveParameter] string $accessKey,
    ): self {
        return new self($username, $accessKey);
    }

    public function hasCredentials(): bool
    {
        return $this->usernameBox !== null && $this->accessKeyBox !== null;
    }

    /**
     * SDK-internal accessor for the raw username.
     *
     * Intended for use by {@see AuthenticationManager} when building the
     * POST /auth request body. Callers must never log, dump, or
     * otherwise persist the returned value.
     */
    public function username(): ?string
    {
        return $this->usernameBox !== null ? ($this->usernameBox)() : null;
    }

    /**
     * SDK-internal accessor for the raw access key. Same handling rules
     * as {@see self::username()} apply.
     */
    public function accessKey(): ?string
    {
        return $this->accessKeyBox !== null ? ($this->accessKeyBox)() : null;
    }

    /**
     * A stable, non-reversible identifier for this exact credential pair,
     * used as the token cache key so different companies (or a rotated
     * access_key for the same username) never collide on, or accidentally
     * reuse, a cached token that belongs to a different credential.
     *
     * @throws \LogicException when no credentials have been set.
     */
    public function fingerprint(): string
    {
        if (! $this->hasCredentials()) {
            throw new \LogicException('Cannot fingerprint '.self::class.' before credentials are set.');
        }

        return hash('sha256', $this->username().'|'.$this->accessKey());
    }

    /**
     * @return array{username: string|null, accessKey: string|null}
     */
    public function __debugInfo(): array
    {
        return [
            'username' => $this->usernameBox !== null ? '[REDACTED]' : null,
            'accessKey' => $this->accessKeyBox !== null ? '[REDACTED]' : null,
        ];
    }

    public function __serialize(): never
    {
        throw new \LogicException(self::class.' must not be serialized.');
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public function __unserialize(array $data): never
    {
        throw new \LogicException(self::class.' must not be unserialized.');
    }
}
