<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Resources;

use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\CreditNote;
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\CreditNoteData;
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\CreditNoteFile;
use Jonathan8312\Siigo\Http\Client;
use Jonathan8312\Siigo\Http\PaginatedResponse;

/**
 * `/v1/credit-notes` — credit notes. See
 * docs/research/siigo-api-co/05-credit-notes.md and docs/credit-notes.md.
 *
 * No `PUT`, `DELETE`, or annul endpoint is documented for credit
 * notes — confirmed both by the official JS SDK (`CreditNoteApi`
 * exposes only create/list/find/pdf) and by `developers.siigo.com`'s
 * own navigation, which lists the same four operations plus the
 * document-types catalog (already covered by
 * {@see Catalogs::documentTypes()} with `type: 'NC'}`). See
 * docs/known-issues.md.
 */
final class CreditNotes
{
    public function __construct(private readonly Client $client) {}

    public function create(CreditNoteData $creditNote, ?string $idempotencyKey = null): CreditNote
    {
        $response = $this->client->post('v1/credit-notes', $creditNote->toArray(), $idempotencyKey);

        return CreditNote::fromArray($this->decode($response->json()));
    }

    public function find(string $id): CreditNote
    {
        $response = $this->client->get("v1/credit-notes/{$id}");

        return CreditNote::fromArray($this->decode($response->json()));
    }

    /**
     * @return PaginatedResponse<CreditNote>
     */
    public function all(
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

        $response = $this->client->get('v1/credit-notes', $query);

        return PaginatedResponse::fromResponse($response, CreditNote::fromArray(...));
    }

    public function pdf(string $id): CreditNoteFile
    {
        $response = $this->client->get("v1/credit-notes/{$id}/pdf");

        return CreditNoteFile::fromArray($this->decode($response->json()));
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
