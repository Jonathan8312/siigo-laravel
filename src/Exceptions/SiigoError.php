<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Exceptions;

/**
 * One entry from Siigo's error response body:
 *
 *   { "Status": 400, "Errors": [{ "Code", "Message", "Params", "Detail" }] }
 *
 * Siigo returns `Errors` as an array — a single request can fail for
 * several fields/reasons at once — so {@see SiigoException} carries a
 * list of these rather than a single message. Field names are
 * PascalCase in Siigo's real response body (confirmed, an exception to
 * the snake_case used everywhere else in the API), captured here as
 * plain lowerCamelCase properties instead.
 */
final class SiigoError
{
    /**
     * @param  list<string>  $params
     */
    public function __construct(
        public readonly ?string $code,
        public readonly ?string $message,
        public readonly array $params,
        public readonly ?string $detail,
    ) {}

    /**
     * @return list<self>
     */
    public static function manyFromResponseBody(mixed $json): array
    {
        if (! is_array($json) || ! is_array($json['Errors'] ?? null)) {
            return [];
        }

        $errors = [];

        foreach ($json['Errors'] as $entry) {
            if (is_array($entry)) {
                $errors[] = self::fromArray($entry);
            }
        }

        return $errors;
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private static function fromArray(array $data): self
    {
        $params = is_array($data['Params'] ?? null) ? $data['Params'] : [];

        return new self(
            code: is_string($data['Code'] ?? null) ? $data['Code'] : null,
            message: is_string($data['Message'] ?? null) ? $data['Message'] : null,
            params: array_values(array_filter($params, is_string(...))),
            detail: is_string($data['Detail'] ?? null) ? $data['Detail'] : null,
        );
    }
}
