<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Support;

use Jonathan8312\Siigo\DataTransferObjects\Invoices\BatchInvoiceItem;
use Jonathan8312\Siigo\Http\Client;

/**
 * @internal Shared format validation for Siigo's idempotency key, used
 * both as the `Idempotency-Key` header on singular document creation
 * ({@see Client::post()}) and as the
 * `idempotency_key` field on each invoice inside a batch
 * ({@see BatchInvoiceItem}).
 * Confirmed empirically against Siigo's real API (see
 * docs/known-issues.md): a hyphen is rejected, the same
 * strict-alphanumeric restriction confirmed for Partner-Id.
 */
final class IdempotencyKey
{
    private const PATTERN = '/^[A-Za-z0-9]{1,30}$/';

    public static function assertValid(string $key): void
    {
        if (preg_match(self::PATTERN, $key) !== 1) {
            throw new \InvalidArgumentException(
                'An idempotency key must be 1-30 alphanumeric characters, with no spaces, '
                ."hyphens, or other special characters. Got: \"{$key}\"."
            );
        }
    }
}
