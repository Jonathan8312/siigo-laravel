<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\Exceptions;

use GuzzleHttp\Psr7\Response;
use Jonathan8312\Siigo\Exceptions\RequestException;
use Jonathan8312\Siigo\Exceptions\SiigoError;
use Jonathan8312\Siigo\Http\SiigoResponse;
use PHPUnit\Framework\TestCase;

final class SiigoExceptionTest extends TestCase
{
    public function test_exposes_status_code_endpoint_errors_and_response(): void
    {
        $response = SiigoResponse::fromIlluminateResponse(
            new \Illuminate\Http\Client\Response(new Response(403, [], '')),
        );
        $error = new SiigoError('forbidden', 'Not allowed', [], null);

        $exception = new RequestException('message', 403, 'GET v1/customers', [$error], $response);

        $this->assertSame(403, $exception->statusCode());
        $this->assertSame('GET v1/customers', $exception->endpoint());
        $this->assertSame([$error], $exception->errors());
        $this->assertSame('forbidden', $exception->errorCode());
        $this->assertSame($response, $exception->response());
    }

    public function test_defaults_are_all_null_or_empty(): void
    {
        $exception = new RequestException('message');

        $this->assertNull($exception->statusCode());
        $this->assertNull($exception->endpoint());
        $this->assertSame([], $exception->errors());
        $this->assertNull($exception->errorCode());
        $this->assertNull($exception->response());
    }

    public function test_preserves_the_previous_exception(): void
    {
        $previous = new \RuntimeException('root cause');
        $exception = new RequestException('message', previous: $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
