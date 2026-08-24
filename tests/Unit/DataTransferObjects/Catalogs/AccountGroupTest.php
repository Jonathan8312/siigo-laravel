<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\DataTransferObjects\Catalogs;

use Jonathan8312\Siigo\DataTransferObjects\Catalogs\AccountGroup;
use PHPUnit\Framework\TestCase;

final class AccountGroupTest extends TestCase
{
    public function test_from_array_maps_the_documented_shape(): void
    {
        $group = AccountGroup::fromArray(['id' => 1253, 'name' => 'Productos', 'active' => true]);

        $this->assertSame(1253, $group->id);
        $this->assertSame('Productos', $group->name);
        $this->assertTrue($group->active);
    }

    public function test_from_array_tolerates_an_empty_payload(): void
    {
        $group = AccountGroup::fromArray([]);

        $this->assertSame(0, $group->id);
        $this->assertSame('', $group->name);
        $this->assertFalse($group->active);
    }
}
