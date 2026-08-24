<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\DataTransferObjects\Catalogs;

use Jonathan8312\Siigo\DataTransferObjects\Catalogs\PriceList;
use PHPUnit\Framework\TestCase;

final class PriceListTest extends TestCase
{
    public function test_from_array_maps_the_documented_shape(): void
    {
        $priceList = PriceList::fromArray(['id' => 2766, 'name' => 'Sale Price 1', 'active' => true, 'position' => 1]);

        $this->assertSame(2766, $priceList->id);
        $this->assertSame('Sale Price 1', $priceList->name);
        $this->assertTrue($priceList->active);
        $this->assertSame(1, $priceList->position);
    }
}
