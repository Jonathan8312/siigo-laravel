<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\DataTransferObjects\Catalogs;

use Jonathan8312\Siigo\DataTransferObjects\Catalogs\DocumentType;
use Jonathan8312\Siigo\Enums\DiscountType;
use PHPUnit\Framework\TestCase;

final class DocumentTypeTest extends TestCase
{
    public function test_from_array_maps_the_documented_shape(): void
    {
        $documentType = DocumentType::fromArray([
            'id' => 5636, 'code' => '1', 'name' => 'Factura', 'description' => 'This is a description',
            'type' => 'FV', 'active' => true, 'seller_by_item' => false, 'cost_center' => false,
            'cost_center_mandatory' => false, 'cost_center_default' => 1235,
            'automatic_number' => true, 'consecutive' => 3, 'discount_type' => 'Value',
            'decimals' => true, 'advance_payment' => false, 'reteiva' => false, 'reteica' => false,
            'self_withholding' => false, 'self_withholding_limit' => 0,
            'electronic_type' => 'NoElectronic', 'official_book' => '0', 'document_support' => false,
            'prefix' => 'FV-1', 'cargo_transportation' => false, 'customer_by_item' => true,
            'global_discounts' => [['id' => 1, 'name' => 'Discount', 'percentage' => 5, 'active' => true]],
            'global_charges' => [['id' => 2, 'name' => 'Charge', 'percentage' => 3, 'active' => true]],
        ]);

        $this->assertSame(5636, $documentType->id);
        $this->assertSame('1', $documentType->code);
        $this->assertSame('Factura', $documentType->name);
        $this->assertSame('FV', $documentType->type);
        $this->assertTrue($documentType->active);
        $this->assertSame(1235, $documentType->costCenterDefault);
        $this->assertSame(3, $documentType->consecutive);
        $this->assertSame('FV-1', $documentType->prefix);
        $this->assertSame(DiscountType::Value, $documentType->discountType);
        $this->assertFalse($documentType->cargoTransportation);
        $this->assertTrue($documentType->customerByItem);

        $this->assertCount(1, $documentType->globalDiscounts);
        $this->assertSame(1, $documentType->globalDiscounts[0]->id);
        $this->assertSame(5.0, $documentType->globalDiscounts[0]->percentage);

        $this->assertCount(1, $documentType->globalCharges);
        $this->assertSame(2, $documentType->globalCharges[0]->id);
    }

    public function test_from_array_tolerates_missing_global_discounts_and_charges(): void
    {
        $documentType = DocumentType::fromArray(['id' => 1]);

        $this->assertSame([], $documentType->globalDiscounts);
        $this->assertSame([], $documentType->globalCharges);
        $this->assertNull($documentType->costCenterDefault);
        $this->assertNull($documentType->prefix);
    }
}
