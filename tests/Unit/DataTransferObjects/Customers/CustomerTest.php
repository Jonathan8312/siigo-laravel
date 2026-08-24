<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\DataTransferObjects\Customers;

use Jonathan8312\Siigo\DataTransferObjects\Customers\Customer;
use Jonathan8312\Siigo\Enums\CustomerType;
use Jonathan8312\Siigo\Enums\PersonType;
use PHPUnit\Framework\TestCase;

final class CustomerTest extends TestCase
{
    public function test_from_array_maps_the_documented_full_response_shape(): void
    {
        $customer = Customer::fromArray([
            'id' => '63f918c2-ca65-4edc-a7db-66bcdd5159fb',
            'type' => 'Customer',
            'person_type' => 'Person',
            'id_type' => ['code' => '13', 'name' => 'Cédula de ciudadanía'],
            'identification' => '13832081',
            'branch_office' => 0,
            'check_digit' => '4',
            'name' => ['string'],
            'commercial_name' => 'string',
            'active' => true,
            'vat_responsible' => false,
            'fiscal_responsibilities' => [['code' => 'R-99-PN', 'name' => 'Not responsible']],
            'address' => [
                'address' => 'Cra. 18 #79A - 42',
                'city' => [
                    'country_code' => 'Co', 'country_name' => 'Colombia',
                    'state_code' => '19', 'state_name' => 'Cauca',
                    'city_code' => '19001', 'city_name' => 'Popayán',
                ],
                'postal_code' => '110911',
            ],
            'phones' => [['indicative' => '57', 'number' => '3006003345', 'extension' => '132']],
            'contacts' => [[
                'first_name' => 'Marcos', 'last_name' => 'Castillo', 'email' => 'marcos@example.com',
                'phone' => ['indicative' => '57', 'number' => '3006003345', 'extension' => '132'],
            ]],
            'comments' => 'This is an additional comment',
            'related_users' => ['seller_id' => 625, 'collector_id' => 625],
            'custom_fields' => [['key' => 'YearsOld', 'value' => '29']],
            'metadata' => ['created' => '2020-06-15T03:33:17.0000000+00:00', 'last_updated' => null],
        ]);

        $this->assertSame('63f918c2-ca65-4edc-a7db-66bcdd5159fb', $customer->id);
        $this->assertSame(CustomerType::Customer, $customer->type);
        $this->assertSame(PersonType::Person, $customer->personType);
        $this->assertNotNull($customer->idType);
        $this->assertSame('13', $customer->idType->code);
        $this->assertSame('Cédula de ciudadanía', $customer->idType->name);
        $this->assertSame(['string'], $customer->name);

        $this->assertNotNull($customer->address);
        $this->assertSame('Cra. 18 #79A - 42', $customer->address->address);
        $this->assertNotNull($customer->address->city);
        $this->assertSame('Popayán', $customer->address->city->cityName);

        $this->assertCount(1, $customer->phones);
        $this->assertSame('3006003345', $customer->phones[0]->number);

        $this->assertCount(1, $customer->contacts);
        $this->assertSame('Marcos', $customer->contacts[0]->firstName);

        $this->assertNotNull($customer->relatedUsers);
        $this->assertSame(625, $customer->relatedUsers->sellerId);

        $this->assertCount(1, $customer->customFields);
        $this->assertSame('YearsOld', $customer->customFields[0]->key);

        $this->assertNotNull($customer->metadata);
        $this->assertSame('2020-06-15T03:33:17.0000000+00:00', $customer->metadata->created);
        $this->assertNull($customer->metadata->lastUpdated);
    }

    public function test_from_array_tolerates_a_minimal_payload(): void
    {
        $customer = Customer::fromArray(['id' => 'abc']);

        $this->assertSame('abc', $customer->id);
        $this->assertSame(CustomerType::Customer, $customer->type);
        $this->assertSame(PersonType::Person, $customer->personType);
        $this->assertNull($customer->idType);
        $this->assertSame([], $customer->name);
        $this->assertNull($customer->address);
        $this->assertSame([], $customer->phones);
        $this->assertNull($customer->relatedUsers);
        $this->assertNull($customer->metadata);
    }
}
