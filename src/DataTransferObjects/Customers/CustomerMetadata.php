<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Customers;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * Kept as raw strings rather than parsed into DateTimeImmutable: Siigo's
 * documented example (`"2020-06-15T03:33:17.0000000+00:00"`) uses a
 * 7-digit fractional-second precision that is not standard ISO-8601 and
 * not guaranteed to parse reliably across PHP versions — silently
 * failing to build a Customer over a timestamp format quirk would be
 * worse than exposing the raw string.
 */
final class CustomerMetadata
{
    public function __construct(
        public readonly ?string $created,
        public readonly ?string $lastUpdated,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            created: ArrayShape::nullableString($data, 'created'),
            lastUpdated: ArrayShape::nullableString($data, 'last_updated'),
        );
    }
}
