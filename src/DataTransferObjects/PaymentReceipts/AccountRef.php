<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts;

use Jonathan8312\Siigo\Enums\AccountMovement;

/**
 * An accounting-account entry on a `Detailed` payment receipt line —
 * `code` is a chart-of-accounts code, `movement` whether this line
 * debits or credits it.
 */
final class AccountRef
{
    public function __construct(
        public readonly string $code,
        public readonly AccountMovement $movement,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'movement' => $this->movement->value,
        ];
    }
}
