<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Resources;

use Jonathan8312\Siigo\DataTransferObjects\Invoices\Invoice;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoiceData;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoiceFile;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\MailStatus;
use Jonathan8312\Siigo\Http\Client;
use Jonathan8312\Siigo\Http\PaginatedResponse;

/**
 * `/v1/invoices` — sales invoices. See docs/research/siigo-api-co/04-invoices.md
 * and docs/invoices.md for the recommended pattern to create many
 * invoices safely (there is no native Siigo "batch" endpoint — see
 * known-issues.md).
 */
final class Invoices
{
    public function __construct(private readonly Client $client) {}

    /**
     * @param  string|null  $idempotencyKey  Strongly recommended when creating many invoices in sequence — see docs/invoices.md.
     */
    public function create(InvoiceData $invoice, ?string $idempotencyKey = null): Invoice
    {
        $response = $this->client->post('v1/invoices', $invoice->toArray(), $idempotencyKey);

        return Invoice::fromArray($this->decode($response->json()));
    }

    public function find(string $id): Invoice
    {
        $response = $this->client->get("v1/invoices/{$id}");

        return Invoice::fromArray($this->decode($response->json()));
    }

    /**
     * A full replace, not a partial patch, matching the equivalent
     * Customers/Products endpoints. Siigo additionally rejects any edit
     * once the invoice is being transmitted to the DIAN or already has
     * a CUFE, or while related documents exist — see {@see InvoiceData}.
     */
    public function update(string $id, InvoiceData $invoice): Invoice
    {
        $response = $this->client->put("v1/invoices/{$id}", $invoice->toArray());

        return Invoice::fromArray($this->decode($response->json()));
    }

    /**
     * @return PaginatedResponse<Invoice>
     */
    public function all(
        ?int $documentId = null,
        ?string $customerIdentification = null,
        ?int $customerBranchOffice = null,
        ?string $name = null,
        ?\DateTimeInterface $createdStart = null,
        ?\DateTimeInterface $createdEnd = null,
        ?\DateTimeInterface $dateStart = null,
        ?\DateTimeInterface $dateEnd = null,
        ?\DateTimeInterface $updatedStart = null,
        ?\DateTimeInterface $updatedEnd = null,
        int $page = 1,
        int $pageSize = 25,
    ): PaginatedResponse {
        $query = array_filter([
            'document_id' => $documentId,
            'customer_identification' => $customerIdentification,
            'customer_branch_office' => $customerBranchOffice,
            'name' => $name,
            'created_start' => self::toRfc3339($createdStart),
            'created_end' => self::toRfc3339($createdEnd),
            'date_start' => self::toRfc3339($dateStart),
            'date_end' => self::toRfc3339($dateEnd),
            'updated_start' => self::toRfc3339($updatedStart),
            'updated_end' => self::toRfc3339($updatedEnd),
            'page' => $page,
            'page_size' => $pageSize,
        ], static fn (mixed $value): bool => $value !== null);

        $response = $this->client->get('v1/invoices', $query);

        return PaginatedResponse::fromResponse($response, Invoice::fromArray(...));
    }

    /**
     * Rejected by Siigo once the invoice is being transmitted to the
     * DIAN, already accepted (has a CUFE), or has related documents —
     * see {@see InvoiceData}.
     */
    public function delete(string $id): bool
    {
        $response = $this->client->delete("v1/invoices/{$id}");

        return self::wasDeleted($response->json());
    }

    /**
     * Marks the invoice as annulled without deleting it (`annulled: true`
     * on subsequent reads) — same restrictions as {@see self::delete()}.
     * No body is sent; Siigo does not document a reason/date field.
     */
    public function annul(string $id): bool
    {
        $response = $this->client->post("v1/invoices/{$id}/annul");

        return self::wasDeleted($response->json());
    }

    /**
     * DIAN rejection messages for an invoice's electronic submission.
     *
     * @return list<string>
     */
    public function stampErrors(string $id): array
    {
        $response = $this->client->get("v1/invoices/{$id}/stamp/errors");
        $json = $response->json();
        $errors = is_array($json) && is_array($json['errors'] ?? null) ? $json['errors'] : [];

        $messages = [];

        foreach ($errors as $error) {
            if (is_array($error) && is_string($error['message'] ?? null)) {
                $messages[] = $error['message'];
            }
        }

        return $messages;
    }

    public function pdf(string $id): InvoiceFile
    {
        $response = $this->client->get("v1/invoices/{$id}/pdf");

        return InvoiceFile::fromArray($this->decode($response->json()));
    }

    public function xml(string $id): InvoiceFile
    {
        $response = $this->client->get("v1/invoices/{$id}/xml");

        return InvoiceFile::fromArray($this->decode($response->json()));
    }

    /**
     * @param  string|null  $copyTo  Up to 5 addresses, separated by `;`.
     */
    public function mail(string $id, string $mailTo, ?string $copyTo = null): MailStatus
    {
        $body = array_filter([
            'guid' => $id,
            'mail_to' => $mailTo,
            'copy_to' => $copyTo,
        ], static fn (mixed $value): bool => $value !== null);

        $response = $this->client->post("v1/invoices/{$id}/mail", $body);

        return MailStatus::fromArray($this->decode($response->json()));
    }

    /**
     * @return array<array-key, mixed>
     */
    private function decode(mixed $json): array
    {
        return is_array($json) ? $json : [];
    }

    private static function wasDeleted(mixed $json): bool
    {
        return ! is_array($json) || ($json['deleted'] ?? true) !== false;
    }

    private static function toRfc3339(?\DateTimeInterface $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return (new \DateTimeImmutable('@'.$date->getTimestamp()))->format('Y-m-d\TH:i:s\Z');
    }
}
