<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\DataTransferObjects\Support;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;
use PHPUnit\Framework\TestCase;

final class ArrayShapeTest extends TestCase
{
    public function test_string_returns_the_value_when_present(): void
    {
        $this->assertSame('hello', ArrayShape::string(['name' => 'hello'], 'name'));
    }

    public function test_string_falls_back_to_default_for_wrong_type_or_missing_key(): void
    {
        $this->assertSame('default', ArrayShape::string(['name' => 42], 'name', 'default'));
        $this->assertSame('default', ArrayShape::string([], 'name', 'default'));
    }

    public function test_nullable_string_returns_null_for_empty_or_missing(): void
    {
        $this->assertNull(ArrayShape::nullableString(['name' => ''], 'name'));
        $this->assertNull(ArrayShape::nullableString([], 'name'));
        $this->assertNull(ArrayShape::nullableString(['name' => 42], 'name'));
    }

    public function test_int_casts_numeric_values(): void
    {
        $this->assertSame(42, ArrayShape::int(['id' => '42'], 'id'));
        $this->assertSame(42, ArrayShape::int(['id' => 42.0], 'id'));
        $this->assertSame(0, ArrayShape::int(['id' => 'not a number'], 'id'));
    }

    public function test_nullable_int_returns_null_for_non_numeric_or_missing(): void
    {
        $this->assertNull(ArrayShape::nullableInt(['id' => 'x'], 'id'));
        $this->assertNull(ArrayShape::nullableInt([], 'id'));
        $this->assertSame(7, ArrayShape::nullableInt(['id' => 7], 'id'));
    }

    public function test_float_casts_numeric_values(): void
    {
        $this->assertSame(19.5, ArrayShape::float(['percentage' => '19.5'], 'percentage'));
        $this->assertSame(0.0, ArrayShape::float(['percentage' => 'x'], 'percentage'));
    }

    public function test_bool_only_accepts_real_booleans(): void
    {
        $this->assertTrue(ArrayShape::bool(['active' => true], 'active'));
        // A non-boolean value ("true" the string) falls back to the given default, not a cast.
        $this->assertTrue(ArrayShape::bool(['active' => 'true'], 'active', true));
        $this->assertFalse(ArrayShape::bool([], 'active'));
    }
}
