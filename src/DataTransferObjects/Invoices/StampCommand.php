<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Invoices;

/**
 * `stamp: { send: true }` on create/update requests electronic
 * submission to the DIAN at the same time. No confirmed standalone
 * endpoint to stamp a document created without this — see
 * docs/known-issues.md.
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
