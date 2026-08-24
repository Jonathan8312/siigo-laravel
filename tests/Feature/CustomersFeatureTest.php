<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Jonathan8312\Siigo\DataTransferObjects\Customers\Address;
use Jonathan8312\Siigo\DataTransferObjects\Customers\City;
use Jonathan8312\Siigo\DataTransferObjects\Customers\CustomerData;
use Jonathan8312\Siigo\DataTransferObjects\Customers\FiscalResponsibility;
use Jonathan8312\Siigo\Enums\PersonType;
use Jonathan8312\Siigo\Exceptions\ValidationException;
use Jonathan8312\Siigo\Siigo;
use Jonathan8312\Siigo\Tests\TestCase;

final class CustomersFeatureTest extends TestCase
{
    public function test_customers_resolves_through_the_container_and_creates_a_customer(): void
    {
        Http::fake([
            'https://siigo.test/auth' => Http::response(['access_token' => 'jwt-value', 'expires_in' => 86400], 200),
            'https://siigo.test/v1/customers' => Http::response([
                'id' => 'abc-123',
                'type' => 'Customer',
                'person_type' => 'Company',
                'identification' => '900123456',
            ], 201),
        ]);

        $siigo = $this->app()->make(Siigo::class);

        $customer = $siigo->customers()->create(new CustomerData(
            personType: PersonType::Company,
            idType: '31',
            identification: '900123456',
            name: ['Acme SAS'],
            fiscalResponsibilities: [new FiscalResponsibility('O-13')],
            address: new Address('Cll. 1', new City('Co', '19', '19001')),
        ));

        $this->assertSame('abc-123', $customer->id);
    }

    public function test_customers_maps_a_validation_error_with_multiple_fields(): void
    {
        Http::fake([
            'https://siigo.test/auth' => Http::response(['access_token' => 'jwt-value', 'expires_in' => 86400], 200),
            'https://siigo.test/v1/customers' => Http::response([
                'Status' => 400,
                'Errors' => [
                    ['Code' => 'parameter_required', 'Message' => 'identification is required', 'Params' => ['identification'], 'Detail' => null],
                    ['Code' => 'parameter_required', 'Message' => 'name is required', 'Params' => ['name'], 'Detail' => null],
                ],
            ], 400),
        ]);

        $siigo = $this->app()->make(Siigo::class);

        try {
            $siigo->customers()->create(new CustomerData(
                personType: PersonType::Company,
                idType: '31',
                identification: '',
                name: [],
                fiscalResponsibilities: [new FiscalResponsibility('O-13')],
                address: new Address('Cll. 1', new City('Co', '19', '19001')),
            ));
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertCount(2, $exception->errors());
        }
    }
}
