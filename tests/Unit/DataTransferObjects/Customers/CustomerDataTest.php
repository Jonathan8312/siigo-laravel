<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\DataTransferObjects\Customers;

use Jonathan8312\Siigo\DataTransferObjects\Customers\Address;
use Jonathan8312\Siigo\DataTransferObjects\Customers\City;
use Jonathan8312\Siigo\DataTransferObjects\Customers\Contact;
use Jonathan8312\Siigo\DataTransferObjects\Customers\CustomerData;
use Jonathan8312\Siigo\DataTransferObjects\Customers\CustomField;
use Jonathan8312\Siigo\DataTransferObjects\Customers\FiscalResponsibility;
use Jonathan8312\Siigo\DataTransferObjects\Customers\Phone;
use Jonathan8312\Siigo\DataTransferObjects\Customers\RelatedUsers;
use Jonathan8312\Siigo\Enums\CustomerType;
use Jonathan8312\Siigo\Enums\PersonType;
use PHPUnit\Framework\TestCase;

final class CustomerDataTest extends TestCase
{
    public function test_to_array_matches_the_documented_full_payload_shape(): void
    {
        $customer = new CustomerData(
            personType: PersonType::Person,
            idType: '13',
            identification: '13832081',
            name: ['string'],
            fiscalResponsibilities: [new FiscalResponsibility('R-99-PN', 'Not responsible')],
            address: new Address('Cra. 18 #79A - 42', new City('Co', '19', '19001'), '110911'),
            checkDigit: '4',
            commercialName: 'string',
            phones: [new Phone('57', '3006003345', '132')],
            contacts: [new Contact('Marcos', 'Castillo', 'marcos@example.com', new Phone('57', '3006003345', '132'))],
            comments: 'This is an additional comment',
            relatedUsers: new RelatedUsers(625, 625),
            customFields: [new CustomField('YearsOld', '29')],
        );

        $this->assertSame([
            'type' => 'Customer',
            'person_type' => 'Person',
            'id_type' => '13',
            'identification' => '13832081',
            'check_digit' => '4',
            'name' => ['string'],
            'commercial_name' => 'string',
            'branch_office' => 0,
            'active' => true,
            'vat_responsible' => false,
            'fiscal_responsibilities' => [['code' => 'R-99-PN', 'name' => 'Not responsible']],
            'address' => [
                'address' => 'Cra. 18 #79A - 42',
                'city' => ['country_code' => 'Co', 'state_code' => '19', 'city_code' => '19001'],
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
        ], $customer->toArray());
    }

    public function test_to_array_omits_optional_fields_when_not_set(): void
    {
        $customer = new CustomerData(
            personType: PersonType::Company,
            idType: '31',
            identification: '900123456',
            name: ['Acme SAS'],
            fiscalResponsibilities: [new FiscalResponsibility('O-13')],
            address: new Address('Cll. 1', new City('Co', '19', '19001')),
        );

        $array = $customer->toArray();

        $this->assertSame(CustomerType::Customer->value, $array['type']);
        $this->assertArrayNotHasKey('check_digit', $array);
        $this->assertArrayNotHasKey('commercial_name', $array);
        $this->assertArrayNotHasKey('phones', $array);
        $this->assertArrayNotHasKey('contacts', $array);
        $this->assertArrayNotHasKey('comments', $array);
        $this->assertArrayNotHasKey('related_users', $array);
        $this->assertArrayNotHasKey('custom_fields', $array);
        $this->assertIsArray($array['address']);
        $this->assertArrayNotHasKey('postal_code', $array['address']);
    }
}
