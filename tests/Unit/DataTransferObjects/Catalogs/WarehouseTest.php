<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\DataTransferObjects\Catalogs;

use Jonathan8312\Siigo\DataTransferObjects\Catalogs\Warehouse;
use PHPUnit\Framework\TestCase;

final class WarehouseTest extends TestCase
{
    public function test_from_array_maps_the_documented_shape(): void
    {
        $warehouse = Warehouse::fromArray(['id' => 1270, 'name' => 'Main Warehouse', 'active' => true, 'has_movements' => false]);

        $this->assertSame(1270, $warehouse->id);
        $this->assertSame('Main Warehouse', $warehouse->name);
        $this->assertTrue($warehouse->active);
        $this->assertFalse($warehouse->hasMovements);
    }
}
