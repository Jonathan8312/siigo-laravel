<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Jonathan8312\Siigo\DataTransferObjects\Products\ProductData;
use Jonathan8312\Siigo\Exceptions\ValidationException;
use Jonathan8312\Siigo\Siigo;
use Jonathan8312\Siigo\Tests\TestCase;

final class ProductsFeatureTest extends TestCase
{
    public function test_products_resolves_through_the_container_and_creates_a_product(): void
    {
        Http::fake([
            'https://siigo.test/auth' => Http::response(['access_token' => 'jwt-value', 'expires_in' => 86400], 200),
            'https://siigo.test/v1/products' => Http::response(['id' => 'abc-123', 'code' => 'Item-1'], 201),
        ]);

        $siigo = $this->app()->make(Siigo::class);

        $product = $siigo->products()->create(new ProductData('Item-1', 'Cotton shirt', 1253));

        $this->assertSame('abc-123', $product->id);
    }

    public function test_products_maps_a_validation_error(): void
    {
        Http::fake([
            'https://siigo.test/auth' => Http::response(['access_token' => 'jwt-value', 'expires_in' => 86400], 200),
            'https://siigo.test/v1/products' => Http::response([
                'Status' => 400,
                'Errors' => [['Code' => 'parameter_required', 'Message' => 'code is required', 'Params' => ['code'], 'Detail' => null]],
            ], 400),
        ]);

        $siigo = $this->app()->make(Siigo::class);

        try {
            $siigo->products()->create(new ProductData('', 'Cotton shirt', 1253));
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertSame('parameter_required', $exception->errorCode());
        }
    }
}
