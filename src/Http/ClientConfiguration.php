<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Http;

use Jonathan8312\Siigo\Auth\AuthCredentials;
use Jonathan8312\Siigo\Siigo;

/**
 * Non-secret HTTP transport configuration for talking to Siigo.
 *
 * Deliberately separate from {@see AuthCredentials}:
 * base URL, Partner-Id, timeouts, and retry behaviour do not vary per
 * company the way login credentials do, so they are not duplicated every
 * time {@see Siigo::withCredentials()} derives a new
 * credential context.
 *
 * Validates eagerly so a misconfigured package fails fast and clearly at
 * boot, rather than surfacing as a confusing Siigo-side rejection later
 * — in particular for Partner-Id, whose format requirement was confirmed
 * empirically against Siigo's real API (see docs/known-issues.md): a
 * value with a hyphen is accepted by nothing in the format description
 * alone, but is rejected by Siigo with `invalid_partner_id`.
 */
final class ClientConfiguration
{
    private const PARTNER_ID_PATTERN = '/^[A-Za-z0-9]{3,100}$/';

    public function __construct(
        public readonly string $baseUrl,
        public readonly string $partnerId,
        public readonly float $connectTimeout,
        public readonly float $timeout,
        public readonly bool $retryEnabled = false,
        public readonly int $retryMaxAttempts = 1,
        public readonly int $retryBackoffMilliseconds = 0,
        public readonly int $maxResponseBytes = 20_000_000,
        public readonly ?string $cacheStore = null,
        public readonly int $tokenSafetyMarginSeconds = 60,
    ) {
        if (trim($this->baseUrl) === '') {
            throw new \InvalidArgumentException(
                'A Siigo base URL is required. Set SIIGO_BASE_URL in your configuration.'
            );
        }

        if (parse_url($this->baseUrl, PHP_URL_SCHEME) !== 'https') {
            throw new \InvalidArgumentException(
                'The Siigo base URL must use HTTPS. Got: '.$this->baseUrl
            );
        }

        if (trim($this->partnerId) === '') {
            throw new \InvalidArgumentException(
                'A Siigo Partner-Id is required. Set SIIGO_PARTNER_ID in your configuration.'
            );
        }

        if (preg_match(self::PARTNER_ID_PATTERN, $this->partnerId) !== 1) {
            throw new \InvalidArgumentException(
                'The Siigo Partner-Id must be 3-100 alphanumeric characters, with no spaces, '
                ."hyphens, or other special characters. Got: \"{$this->partnerId}\"."
            );
        }

        if ($this->connectTimeout <= 0.0 || $this->timeout <= 0.0) {
            throw new \InvalidArgumentException(
                'Siigo connection and request timeouts must be greater than zero.'
            );
        }

        if ($this->retryMaxAttempts < 1) {
            throw new \InvalidArgumentException(
                'Siigo retry max attempts must be at least 1.'
            );
        }

        if ($this->retryBackoffMilliseconds < 0) {
            throw new \InvalidArgumentException(
                'Siigo retry backoff must not be negative.'
            );
        }

        if ($this->maxResponseBytes < 1) {
            throw new \InvalidArgumentException(
                'Siigo max response size must be at least 1 byte.'
            );
        }

        if ($this->tokenSafetyMarginSeconds < 0) {
            throw new \InvalidArgumentException(
                'Siigo token safety margin must not be negative.'
            );
        }
    }
}
