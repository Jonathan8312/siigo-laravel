<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Http;

/**
 * Siigo's paginated list envelope, confirmed identical across every
 * list endpoint investigated so far (invoices, customers, the users
 * catalog): `{ pagination: { page, page_size, total_results }, results,
 * __links }`. `__links` is intentionally not modeled here — it only
 * carries `href`s Siigo itself already encodes via `page`, and one
 * source in docs/research disagreed on `_links` vs `__links` naming.
 *
 * See docs/known-issues.md: a requested `page_size` was not always
 * honored by Siigo in testing — always read `pageSize` back from the
 * response rather than assuming the request value was applied.
 *
 * @template TItem
 */
final class PaginatedResponse
{
    /**
     * @param  list<TItem>  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $page,
        public readonly int $pageSize,
        public readonly int $totalResults,
    ) {}

    /**
     * @template TMapped
     *
     * @param  \Closure(array<array-key, mixed>): TMapped  $mapItem
     * @return self<TMapped>
     */
    public static function fromResponse(SiigoResponse $response, \Closure $mapItem): self
    {
        $json = $response->json();
        $body = is_array($json) ? $json : [];

        $pagination = is_array($body['pagination'] ?? null) ? $body['pagination'] : [];
        $results = is_array($body['results'] ?? null) ? $body['results'] : [];

        $items = [];

        foreach ($results as $entry) {
            if (is_array($entry)) {
                $items[] = $mapItem($entry);
            }
        }

        return new self(
            items: $items,
            page: is_numeric($pagination['page'] ?? null) ? (int) $pagination['page'] : 1,
            pageSize: is_numeric($pagination['page_size'] ?? null) ? (int) $pagination['page_size'] : count($items),
            totalResults: is_numeric($pagination['total_results'] ?? null) ? (int) $pagination['total_results'] : count($items),
        );
    }
}
