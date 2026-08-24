<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Exceptions;

use Jonathan8312\Siigo\Http\Client;
use Jonathan8312\Siigo\Http\SiigoResponse;

/**
 * Thrown when Siigo responds with HTTP 429 — the documented limit of
 * 100 requests/minute in production (10/minute on trial accounts) was
 * exceeded.
 *
 * Never retried automatically by {@see Client}:
 * Siigo documents a policy of blocking accounts whose error rate stays
 * above 80% over 7 days, so blindly retrying a rate-limited request
 * risks making that worse. Callers should back off themselves, using
 * {@see self::retryAfterSeconds()} when Siigo provides it.
 */
final class RateLimitException extends SiigoException
{
    /**
     * @param  list<SiigoError>  $errors
     */
    public function __construct(
        string $message,
        ?int $statusCode = null,
        ?string $endpoint = null,
        array $errors = [],
        ?SiigoResponse $response = null,
        private readonly ?int $retryAfterSeconds = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $endpoint, $errors, $response, $previous);
    }

    /**
     * Seconds to wait before retrying, from Siigo's `Retry-After`
     * header, when present. Null when Siigo did not send one — not
     * confirmed to be sent at all as of this writing (see
     * docs/research/siigo-api-co/00-core-auth-http.md).
     */
    public function retryAfterSeconds(): ?int
    {
        return $this->retryAfterSeconds;
    }
}
