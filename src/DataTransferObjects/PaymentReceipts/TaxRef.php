<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts;

/**
 * A tax reference on a `Detailed` payment receipt item — only `id` is
 * sent; the tax `base`/`percentage`/`value` are computed server-side
 * from the item's `value` (not confirmed against sandbox — see
 * docs/known-issues.md).
 */
final class TaxRef
{
    public function __construct(
        public readonly int $id,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['id' => $this->id];
    }
}
