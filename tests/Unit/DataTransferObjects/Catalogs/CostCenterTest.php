<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\DataTransferObjects\Catalogs;

use Jonathan8312\Siigo\DataTransferObjects\Catalogs\CostCenter;
use PHPUnit\Framework\TestCase;

final class CostCenterTest extends TestCase
{
    public function test_from_array_maps_the_documented_shape(): void
    {
        $costCenter = CostCenter::fromArray(['id' => 13222, 'code' => '1112', 'name' => 'center', 'active' => true]);

        $this->assertSame(13222, $costCenter->id);
        $this->assertSame('1112', $costCenter->code);
        $this->assertSame('center', $costCenter->name);
        $this->assertTrue($costCenter->active);
    }
}
