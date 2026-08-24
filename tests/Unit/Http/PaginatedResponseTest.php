<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\Http;

use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response as IlluminateResponse;
use Jonathan8312\Siigo\Http\PaginatedResponse;
use Jonathan8312\Siigo\Http\SiigoResponse;
use PHPUnit\Framework\TestCase;

final class PaginatedResponseTest extends TestCase
{
    public function test_maps_items_and_pagination_metadata(): void
    {
        $response = $this->wrap([
            'pagination' => ['page' => 2, 'page_size' => 25, 'total_results' => 250],
            'results' => [['name' => 'a'], ['name' => 'b']],
        ]);

        $paginated = PaginatedResponse::fromResponse($response, fn (array $row): string => is_string($row['name'] ?? null) ? $row['name'] : '');

        $this->assertSame(['a', 'b'], $paginated->items);
        $this->assertSame(2, $paginated->page);
        $this->assertSame(25, $paginated->pageSize);
        $this->assertSame(250, $paginated->totalResults);
    }

    public function test_tolerates_a_missing_pagination_envelope(): void
    {
        $response = $this->wrap(['results' => [['name' => 'a']]]);

        $paginated = PaginatedResponse::fromResponse($response, fn (array $row): string => is_string($row['name'] ?? null) ? $row['name'] : '');

        $this->assertSame(['a'], $paginated->items);
        $this->assertSame(1, $paginated->page);
        $this->assertSame(1, $paginated->pageSize);
        $this->assertSame(1, $paginated->totalResults);
    }

    public function test_tolerates_a_missing_results_key(): void
    {
        $response = $this->wrap(['pagination' => ['page' => 1, 'page_size' => 25, 'total_results' => 0]]);

        $paginated = PaginatedResponse::fromResponse($response, fn (array $row): string => is_string($row['name'] ?? null) ? $row['name'] : '');

        $this->assertSame([], $paginated->items);
        $this->assertSame(0, $paginated->totalResults);
    }

    public function test_skips_non_array_entries_in_results(): void
    {
        $response = $this->wrap(['results' => [['name' => 'a'], 'not-an-array', null]]);

        $paginated = PaginatedResponse::fromResponse($response, fn (array $row): string => is_string($row['name'] ?? null) ? $row['name'] : '');

        $this->assertSame(['a'], $paginated->items);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function wrap(array $body): SiigoResponse
    {
        return SiigoResponse::fromIlluminateResponse(
            new IlluminateResponse(new Psr7Response(200, [], json_encode($body, JSON_THROW_ON_ERROR))),
        );
    }
}
