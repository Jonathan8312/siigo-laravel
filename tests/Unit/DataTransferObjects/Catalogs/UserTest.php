<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\DataTransferObjects\Catalogs;

use Jonathan8312\Siigo\DataTransferObjects\Catalogs\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function test_from_array_maps_the_documented_shape(): void
    {
        $user = User::fromArray([
            'id' => 35071,
            'username' => 'DavidYepes27',
            'first_name' => 'James David',
            'last_name' => 'Freeman Smith',
            'email' => 'james@example.com',
            'active' => true,
            'identification' => '13832082',
        ]);

        $this->assertSame(35071, $user->id);
        $this->assertSame('DavidYepes27', $user->username);
        $this->assertSame('James David', $user->firstName);
        $this->assertSame('Freeman Smith', $user->lastName);
        $this->assertSame('james@example.com', $user->email);
        $this->assertTrue($user->active);
        $this->assertSame('13832082', $user->identification);
    }
}
