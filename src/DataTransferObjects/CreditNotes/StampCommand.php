<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\CreditNotes;

/**
 * `stamp: { send: true }` on `POST /v1/credit-notes` requests
 * electronic submission to the DIAN at the same time as creation. No
 * confirmed standalone endpoint to stamp a credit note created without
 * this — see docs/known-issues.md.
 */
final class StampCommand
{
    public function __construct(
        public readonly bool $send,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['send' => $this->send];
    }
}
