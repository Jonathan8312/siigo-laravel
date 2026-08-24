<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\DataTransferObjects\Catalogs;

use Jonathan8312\Siigo\DataTransferObjects\Catalogs\Tax;
use PHPUnit\Framework\TestCase;

final class TaxTest extends TestCase
{
    public function test_from_array_maps_the_documented_shape(): void
    {
        $tax = Tax::fromArray(['id' => 13156, 'name' => 'IVA 19%', 'type' => 'IVA', 'percentage' => 19, 'active' => true]);

        $this->assertSame(13156, $tax->id);
        $this->assertSame('IVA 19%', $tax->name);
        $this->assertSame('IVA', $tax->type);
        $this->assertSame(19.0, $tax->percentage);
        $this->assertTrue($tax->active);
    }
}
