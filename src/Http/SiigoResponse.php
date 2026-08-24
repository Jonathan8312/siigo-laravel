<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Http;

use Illuminate\Http\Client\Response as IlluminateResponse;

/**
 * A stable, minimal wrapper around a Siigo HTTP response.
 *
 * Preserves the HTTP status, the original body, and the headers in one
 * predictable shape, plus a best-effort decoded JSON view. Per-resource
 * typed response objects are introduced module by module, once real
 * response structures have been captured and verified, but they must
 * not remove access to this raw representation.
 */
final class SiigoResponse
{
    /**
     * @param  array<string, array<int, string>>  $headers
     */
    public function __construct(
        private readonly int $status,
        private readonly string $body,
        private readonly array $headers,
    ) {}

    public static function fromIlluminateResponse(IlluminateResponse $response): self
    {
        return new self($response->status(), $response->body(), self::normalizeHeaders($response->headers()));
    }

    /**
     * Illuminate\Http\Client\Response::headers() is untyped beyond
     * `array`; normalize it defensively rather than trusting its shape.
     *
     * @param  array<array-key, mixed>  $headers
     * @return array<string, array<int, string>>
     */
    private static function normalizeHeaders(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $name => $values) {
            if (! is_string($name)) {
                continue;
            }

            $values = is_array($values) ? $values : [$values];

            $normalized[$name] = array_values(array_map(
                static fn (mixed $value): string => is_scalar($value) ? (string) $value : '',
                $values,
            ));
        }

        return $normalized;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function successful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function failed(): bool
    {
        return ! $this->successful();
    }

    public function body(): string
    {
        return $this->body;
    }

    /**
     * Best-effort decoded JSON body. Returns null when the body is empty
     * or is not valid JSON, rather than throwing.
     */
    public function json(): mixed
    {
        if (trim($this->body) === '') {
            return null;
        }

        $decoded = json_decode($this->body, associative: true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function header(string $name): ?string
    {
        foreach ($this->headers as $key => $values) {
            if (strcasecmp($key, $name) === 0) {
                return $values[0] ?? null;
            }
        }

        return null;
    }
}
