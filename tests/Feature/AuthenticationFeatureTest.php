<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Jonathan8312\Siigo\Exceptions\NotFoundException;
use Jonathan8312\Siigo\Siigo;
use Jonathan8312\Siigo\Tests\TestCase;

/**
 * Exercises the full container wiring end to end (Fase 1 completion
 * criterion: the SDK can authenticate against Siigo and perform a
 * generic request), using Http::fake() so no real network call happens.
 */
final class AuthenticationFeatureTest extends TestCase
{
    public function test_a_generic_request_authenticates_once_and_sends_the_expected_headers(): void
    {
        Http::fake([
            'https://siigo.test/auth' => Http::response(['access_token' => 'jwt-value', 'expires_in' => 86400], 200),
            'https://siigo.test/v1/ping*' => Http::response(['ok' => true], 200),
        ]);

        $siigo = $this->app()->make(Siigo::class);

        $first = $siigo->client()->get('v1/ping');
        $second = $siigo->client()->get('v1/ping');

        $this->assertTrue($first->successful());
        $this->assertTrue($second->successful());

        Http::assertSentCount(3); // 1 auth + 2 generic requests, token cached across both
        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Partner-Id', 'TestingPartner'));
    }

    public function test_a_generic_request_maps_a_documented_error_to_the_right_exception(): void
    {
        Http::fake([
            'https://siigo.test/auth' => Http::response(['access_token' => 'jwt-value', 'expires_in' => 86400], 200),
            'https://siigo.test/v1/customers/missing*' => Http::response([
                'Status' => 404,
                'Errors' => [['Code' => 'not_found', 'Message' => 'Customer not found', 'Params' => [], 'Detail' => null]],
            ], 404),
        ]);

        $siigo = $this->app()->make(Siigo::class);

        $this->expectException(NotFoundException::class);

        $siigo->client()->get('v1/customers/missing');
    }
}
