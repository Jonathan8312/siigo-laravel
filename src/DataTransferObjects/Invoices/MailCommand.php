<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Invoices;

use Jonathan8312\Siigo\Resources\Invoices;

/**
 * `mail: { send: true }` on create/update requests emailing the
 * invoice to the customer at the same time. To email an existing
 * invoice on demand instead, use {@see Invoices::mail()}.
 */
final class MailCommand
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
