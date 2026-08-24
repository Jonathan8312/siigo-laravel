<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\DataTransferObjects\Customers;

use Jonathan8312\Siigo\DataTransferObjects\Customers\Phone;
use PHPUnit\Framework\TestCase;

final class PhoneTest extends TestCase
{
    public function test_to_array_includes_every_field_when_present(): void
    {
        $phone = new Phone(indicative: '57', number: '3006003345', extension: '132');

        $this->assertSame(['indicative' => '57', 'number' => '3006003345', 'extension' => '132'], $phone->toArray());
    }

    public function test_to_array_omits_null_fields(): void
    {
        $phone = new Phone(number: '3006003345');

        $this->assertSame(['number' => '3006003345'], $phone->toArray());
    }

    public function test_from_array_round_trips(): void
    {
        $phone = Phone::fromArray(['indicative' => '57', 'number' => '3006003345', 'extension' => '132']);

        $this->assertSame('57', $phone->indicative);
        $this->assertSame('3006003345', $phone->number);
        $this->assertSame('132', $phone->extension);
    }
}
