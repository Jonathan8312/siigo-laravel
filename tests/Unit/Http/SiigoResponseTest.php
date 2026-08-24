<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\Http;

use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response as IlluminateResponse;
use Jonathan8312\Siigo\Http\SiigoResponse;
use PHPUnit\Framework\TestCase;

final class SiigoResponseTest extends TestCase
{
    public function test_wraps_status_body_and_headers(): void
    {
        $response = $this->wrap(200, '{"id":"abc"}', ['X-Test' => 'value']);

        $this->assertSame(200, $response->status());
        $this->assertTrue($response->successful());
        $this->assertFalse($response->failed());
        $this->assertSame('{"id":"abc"}', $response->body());
        $this->assertSame('value', $response->header('X-Test'));
        $this->assertSame('value', $response->header('x-test'));
    }

    public function test_failed_reflects_non_2xx_status(): void
    {
        $response = $this->wrap(404, '', []);

        $this->assertFalse($response->successful());
        $this->assertTrue($response->failed());
    }

    public function test_json_decodes_a_valid_body(): void
    {
        $response = $this->wrap(200, '{"pagination":{"page":1}}', []);

        $this->assertSame(['pagination' => ['page' => 1]], $response->json());
    }

    public function test_json_returns_null_for_an_empty_body(): void
    {
        $response = $this->wrap(204, '', []);

        $this->assertNull($response->json());
    }

    public function test_json_returns_null_for_a_malformed_body(): void
    {
        $response = $this->wrap(200, 'not json', []);

        $this->assertNull($response->json());
    }

    public function test_header_returns_null_when_absent(): void
    {
        $response = $this->wrap(200, '', []);

        $this->assertNull($response->header('X-Missing'));
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function wrap(int $status, string $body, array $headers): SiigoResponse
    {
        return SiigoResponse::fromIlluminateResponse(
            new IlluminateResponse(new Psr7Response($status, $headers, $body)),
        );
    }
}
