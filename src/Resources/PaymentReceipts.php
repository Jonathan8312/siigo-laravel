<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Resources;

use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\DetailedPaymentReceiptData;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\PaymentReceipt;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\PaymentReceiptData;
use Jonathan8312\Siigo\Http\Client;
use Jonathan8312\Siigo\Http\PaginatedResponse;

/**
 * `/v1/payment-receipts` — payment receipts to suppliers (recibos de
 * pago/egreso), the purchase-side counterpart of vouchers (recibos de
 * caja to customers, not yet implemented). See
 * docs/research/siigo-api-co/09-payment-receipts.md and
 * docs/payment-receipts.md.
 *
 * Unlike {@see CreditNotes}, `PUT` and `DELETE` are both confirmed
 * against real sandbox behaviour despite gaps in Siigo's own `.apib`
 * spec — see docs/known-issues.md.
 */
final class PaymentReceipts
{
    public function __construct(private readonly Client $client) {}

    public function create(PaymentReceiptData|DetailedPaymentReceiptData $paymentReceipt, ?string $idempotencyKey = null): PaymentReceipt
    {
        $response = $this->client->post('v1/payment-receipts', $paymentReceipt->toArray(), $idempotencyKey);

        return PaymentReceipt::fromArray($this->decode($response->json()));
    }

    public function find(string $id): PaymentReceipt
    {
        $response = $this->client->get("v1/payment-receipts/{$id}");

        return PaymentReceipt::fromArray($this->decode($response->json()));
    }

    public function update(string $id, PaymentReceiptData|DetailedPaymentReceiptData $paymentReceipt): PaymentReceipt
    {
        $response = $this->client->put("v1/payment-receipts/{$id}", $paymentReceipt->toArray());

        return PaymentReceipt::fromArray($this->decode($response->json()));
    }

    /**
     * @return PaginatedResponse<PaymentReceipt>
     */
    public function all(
        ?\DateTimeInterface $createdStart = null,
        ?\DateTimeInterface $createdEnd = null,
        ?\DateTimeInterface $updatedStart = null,
        ?\DateTimeInterface $updatedEnd = null,
        int $page = 1,
        int $pageSize = 25,
    ): PaginatedResponse {
        $query = array_filter([
            'created_start' => self::toRfc3339($createdStart),
            'created_end' => self::toRfc3339($createdEnd),
            'updated_start' => self::toRfc3339($updatedStart),
            'updated_end' => self::toRfc3339($updatedEnd),
            'page' => $page,
            'page_size' => $pageSize,
        ], static fn (mixed $value): bool => $value !== null);

        $response = $this->client->get('v1/payment-receipts', $query);

        return PaginatedResponse::fromResponse($response, PaymentReceipt::fromArray(...));
    }

    /**
     * Siigo's documented response is `{id, deleted}` — returns whether
     * deletion succeeded rather than void. Rejected with
     * `delete_not_allowed` when the receipt has related documents — see
     * docs/known-issues.md.
     */
    public function delete(string $id): bool
    {
        $response = $this->client->delete("v1/payment-receipts/{$id}");
        $json = $response->json();

        return ! is_array($json) || ($json['deleted'] ?? true) !== false;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function decode(mixed $json): array
    {
        return is_array($json) ? $json : [];
    }

    private static function toRfc3339(?\DateTimeInterface $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return (new \DateTimeImmutable('@'.$date->getTimestamp()))->format('Y-m-d\TH:i:s\Z');
    }
}
