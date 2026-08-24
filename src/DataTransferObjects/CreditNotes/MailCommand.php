<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\CreditNotes;

use Jonathan8312\Siigo\Resources\Invoices;

/**
 * `mail: { send: true }` on `POST /v1/credit-notes` requests emailing
 * the credit note to the customer at creation time. Unlike
 * {@see Invoices::mail()}, no standalone
 * "send by mail" endpoint is documented for credit notes — this is the
 * only confirmed way to email one.
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
