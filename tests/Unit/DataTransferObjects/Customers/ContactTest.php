<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\DataTransferObjects\Customers;

use Jonathan8312\Siigo\DataTransferObjects\Customers\Contact;
use Jonathan8312\Siigo\DataTransferObjects\Customers\Phone;
use PHPUnit\Framework\TestCase;

final class ContactTest extends TestCase
{
    public function test_to_array_includes_a_nested_phone_when_present(): void
    {
        $contact = new Contact(firstName: 'Marcos', lastName: 'Castillo', email: 'marcos@example.com', phone: new Phone(number: '3006003345'));

        $this->assertSame([
            'first_name' => 'Marcos',
            'last_name' => 'Castillo',
            'email' => 'marcos@example.com',
            'phone' => ['number' => '3006003345'],
        ], $contact->toArray());
    }

    public function test_to_array_omits_a_missing_phone(): void
    {
        $contact = new Contact(firstName: 'Marcos');

        $this->assertSame(['first_name' => 'Marcos'], $contact->toArray());
    }

    public function test_from_array_maps_a_nested_phone(): void
    {
        $contact = Contact::fromArray([
            'first_name' => 'Marcos', 'last_name' => 'Castillo', 'email' => 'marcos@example.com',
            'phone' => ['indicative' => '57', 'number' => '3006003345', 'extension' => '132'],
        ]);

        $this->assertSame('Marcos', $contact->firstName);
        $this->assertNotNull($contact->phone);
        $this->assertSame('3006003345', $contact->phone->number);
    }

    public function test_from_array_tolerates_a_missing_phone(): void
    {
        $contact = Contact::fromArray(['first_name' => 'Marcos']);

        $this->assertNull($contact->phone);
    }
}
