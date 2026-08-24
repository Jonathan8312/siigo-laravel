<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\DataTransferObjects\Catalogs;

use Jonathan8312\Siigo\DataTransferObjects\Catalogs\PaymentType;
use Jonathan8312\Siigo\Enums\PaymentTypeApplicability;
use PHPUnit\Framework\TestCase;

final class PaymentTypeTest extends TestCase
{
    public function test_from_array_maps_the_documented_shape(): void
    {
        $paymentType = PaymentType::fromArray([
            'id' => 5636,
            'name' => 'Crédito',
            'type' => 'Cartera',
            'active' => true,
            'due_date' => true,
        ]);

        $this->assertSame(5636, $paymentType->id);
        $this->assertSame('Crédito', $paymentType->name);
        $this->assertSame(PaymentTypeApplicability::Cartera, $paymentType->applicability);
        $this->assertTrue($paymentType->active);
        $this->assertTrue($paymentType->dueDate);
    }

    public function test_from_array_maps_the_slash_separated_applicability(): void
    {
        $paymentType = PaymentType::fromArray(['type' => 'Cartera/Proveedor']);

        $this->assertSame(PaymentTypeApplicability::CarteraProveedor, $paymentType->applicability);
    }

    public function test_from_array_tolerates_an_unknown_applicability_value(): void
    {
        $paymentType = PaymentType::fromArray(['type' => 'SomethingSiigoAddsLater']);

        $this->assertNull($paymentType->applicability);
    }

    public function test_from_array_leaves_applicability_null_when_absent(): void
    {
        $paymentType = PaymentType::fromArray([]);

        $this->assertNull($paymentType->applicability);
    }
}
