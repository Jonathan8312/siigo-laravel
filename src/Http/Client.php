<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Http;

use Illuminate\Http\Client\ConnectionException as IlluminateConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response as IlluminateResponse;
use Jonathan8312\Siigo\Auth\AuthenticationManager;
use Jonathan8312\Siigo\Exceptions\AuthenticationException;
use Jonathan8312\Siigo\Exceptions\ConnectionException;
use Jonathan8312\Siigo\Exceptions\NotFoundException;
use Jonathan8312\Siigo\Exceptions\RateLimitException;
use Jonathan8312\Siigo\Exceptions\RequestException;
use Jonathan8312\Siigo\Exceptions\ServerException;
use Jonathan8312\Siigo\Exceptions\SiigoError;
use Jonathan8312\Siigo\Exceptions\ValidationException;
use Jonathan8312\Siigo\Siigo;

/**
 * The single point of contact between the SDK and Siigo.
 *
 * Owns base URL resolution, the Authorization/Partner-Id/Idempotency-Key
 * headers, timeouts, safe retry behaviour, and translation of
 * transport/HTTP failures into the SDK's own exception hierarchy. No
 * resource class should talk to {@see HttpFactory} directly.
 *
 * Never follows HTTP redirects ({@see self::newRequest()}): silently
 * following a 3xx response could resend the Bearer token to an
 * unintended host or replay a side-effecting request against an
 * unexpected URL.
 *
 * Immutable: {@see self::withAuthenticationManager()} returns a new,
 * detached Client instance and never mutates the one it was called on,
 * which is what makes {@see Siigo::withCredentials()}
 * safe to use against a container singleton under Octane/long-running
 * workers.
 */
final class Client
{
    /**
     * HTTP status codes that are safe to retry automatically, and only
     * ever for idempotent (GET) requests. 429 is deliberately excluded —
     * see {@see RateLimitException}.
     */
    private const RETRYABLE_STATUS_CODES = [502, 503, 504];

    /**
     * Confirmed empirically against Siigo's real API (see
     * docs/known-issues.md): a hyphen is rejected, matching the same
     * strict-alphanumeric behavior already confirmed for Partner-Id.
     */
    private const IDEMPOTENCY_KEY_PATTERN = '/^[A-Za-z0-9]{1,30}$/';

    public function __construct(
        private readonly HttpFactory $http,
        private readonly AuthenticationManager $auth,
        private readonly ClientConfiguration $config,
    ) {}

    public function withAuthenticationManager(AuthenticationManager $auth): self
    {
        return new self($this->http, $auth, $this->config);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function get(string $path, array $query = []): SiigoResponse
    {
        return $this->send(
            'GET',
            $path,
            null,
            fn (PendingRequest $request): IlluminateResponse => $request->get($this->normalizePath($path), $query),
        );
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public function post(string $path, array $body = [], ?string $idempotencyKey = null): SiigoResponse
    {
        if ($idempotencyKey !== null && preg_match(self::IDEMPOTENCY_KEY_PATTERN, $idempotencyKey) !== 1) {
            throw new \InvalidArgumentException(
                'An Idempotency-Key must be 1-30 alphanumeric characters, with no spaces, '
                ."hyphens, or other special characters. Got: \"{$idempotencyKey}\"."
            );
        }

        return $this->send(
            'POST',
            $path,
            $idempotencyKey,
            fn (PendingRequest $request): IlluminateResponse => $request->post($this->normalizePath($path), $body),
        );
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public function put(string $path, array $body = []): SiigoResponse
    {
        return $this->send(
            'PUT',
            $path,
            null,
            fn (PendingRequest $request): IlluminateResponse => $request->put($this->normalizePath($path), $body),
        );
    }

    public function delete(string $path): SiigoResponse
    {
        return $this->send(
            'DELETE',
            $path,
            null,
            fn (PendingRequest $request): IlluminateResponse => $request->delete($this->normalizePath($path)),
        );
    }

    /**
     * @param  \Closure(PendingRequest): IlluminateResponse  $perform
     */
    private function send(string $method, string $path, ?string $idempotencyKey, \Closure $perform): SiigoResponse
    {
        $endpoint = "{$method} {$this->normalizePath($path)}";

        try {
            return $this->attempt($endpoint, $perform, $idempotencyKey);
        } catch (AuthenticationException $exception) {
            if ($exception->statusCode() !== 401) {
                throw $exception;
            }

            // The cached token looked valid but Siigo itself rejected it
            // mid-request (e.g. revoked before its cached expiry). A 401
            // means Siigo never reached the business logic for this
            // request, so resending after a forced re-login is safe even
            // for POST/PUT/DELETE — this is not "retrying a failed
            // write", it is "the write never actually happened yet".
            // Exactly one forced login, never more, so a persistently
            // broken credential fails fast instead of looping.
            $this->auth->refresh();

            return $this->attempt($endpoint, $perform, $idempotencyKey);
        }
    }

    /**
     * @param  \Closure(PendingRequest): IlluminateResponse  $perform
     */
    private function attempt(string $endpoint, \Closure $perform, ?string $idempotencyKey): SiigoResponse
    {
        $retryable = str_starts_with($endpoint, 'GET ') && $this->config->retryEnabled;
        $maxAttempts = $retryable ? max(1, $this->config->retryMaxAttempts) : 1;

        for ($attemptNumber = 1; $attemptNumber <= $maxAttempts; $attemptNumber++) {
            $token = $this->auth->token();

            try {
                $response = $perform($this->newRequest($token, $idempotencyKey));
            } catch (IlluminateConnectionException $exception) {
                if ($attemptNumber < $maxAttempts) {
                    $this->waitBeforeRetry();

                    continue;
                }

                throw new ConnectionException(
                    "Unable to reach Siigo while performing {$endpoint}.",
                    endpoint: $endpoint,
                    previous: $exception,
                );
            }

            if ($retryable && $attemptNumber < $maxAttempts && in_array($response->status(), self::RETRYABLE_STATUS_CODES, true)) {
                $this->waitBeforeRetry();

                continue;
            }

            return $this->toSiigoResponseOrThrow($response, $endpoint);
        }

        // Unreachable: the loop above always returns or throws before
        // exhausting its bound. Kept only so static analysis can see
        // this method never implicitly falls through.
        throw new ConnectionException("Unable to reach Siigo while performing {$endpoint}.", endpoint: $endpoint);
    }

    private function newRequest(string $token, ?string $idempotencyKey): PendingRequest
    {
        $request = $this->http
            ->baseUrl(rtrim($this->config->baseUrl, '/'))
            ->withToken($token)
            ->withHeaders(['Partner-Id' => $this->config->partnerId])
            ->acceptJson()
            ->asJson()
            ->connectTimeout((int) $this->config->connectTimeout)
            ->timeout((int) $this->config->timeout)
            ->withoutRedirecting();

        if ($idempotencyKey !== null) {
            $request = $request->withHeaders(['Idempotency-Key' => $idempotencyKey]);
        }

        return $request;
    }

    private function toSiigoResponseOrThrow(IlluminateResponse $response, string $endpoint): SiigoResponse
    {
        $siigoResponse = SiigoResponse::fromIlluminateResponse($response);
        $bodySize = strlen($siigoResponse->body());

        if ($bodySize > $this->config->maxResponseBytes) {
            throw new RequestException(
                "Siigo response for {$endpoint} exceeded the configured maximum size of ".
                "{$this->config->maxResponseBytes} bytes ({$bodySize} bytes received).",
                $siigoResponse->status(),
                $endpoint,
                [],
                $siigoResponse,
            );
        }

        if ($siigoResponse->successful()) {
            return $siigoResponse;
        }

        $status = $siigoResponse->status();
        $errors = SiigoError::manyFromResponseBody($siigoResponse->json());
        $message = $this->describeFailure($endpoint, $status, $errors);

        throw match (true) {
            $status === 400 => new ValidationException($message, $status, $endpoint, $errors, $siigoResponse),
            $status === 401 => new AuthenticationException($message, $status, $endpoint, $errors, $siigoResponse),
            $status === 404 => new NotFoundException($message, $status, $endpoint, $errors, $siigoResponse),
            $status === 429 => new RateLimitException(
                $message,
                $status,
                $endpoint,
                $errors,
                $siigoResponse,
                retryAfterSeconds: self::parseRetryAfter($siigoResponse),
            ),
            in_array($status, [408, 500, 503, 504], true) => new ServerException($message, $status, $endpoint, $errors, $siigoResponse),
            default => new RequestException($message, $status, $endpoint, $errors, $siigoResponse),
        };
    }

    /**
     * @param  list<SiigoError>  $errors
     */
    private function describeFailure(string $endpoint, int $status, array $errors): string
    {
        $first = $errors[0] ?? null;

        if ($first !== null && $first->code !== null) {
            return "Siigo rejected {$endpoint} (HTTP {$status}: {$first->code} - ".($first->message ?? 'no message').').';
        }

        return "Siigo returned an unsuccessful response for {$endpoint} (HTTP {$status}).";
    }

    private static function parseRetryAfter(SiigoResponse $response): ?int
    {
        $header = $response->header('Retry-After');

        return $header !== null && ctype_digit($header) ? (int) $header : null;
    }

    private function normalizePath(string $path): string
    {
        return ltrim($path, '/');
    }

    private function waitBeforeRetry(): void
    {
        if ($this->config->retryBackoffMilliseconds > 0) {
            usleep($this->config->retryBackoffMilliseconds * 1000);
        }
    }
}
