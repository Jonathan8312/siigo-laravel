<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\DataTransferObjects\Products;

use Jonathan8312\Siigo\DataTransferObjects\Products\Product;
use Jonathan8312\Siigo\Enums\ProductType;
use Jonathan8312\Siigo\Enums\TaxClassification;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    public function test_from_array_maps_the_documented_full_response_shape(): void
    {
        $product = Product::fromArray([
            'id' => '497f6eca-6276-4993-bfeb-53cbbbba6f08',
            'code' => 'Item-1',
            'name' => 'Cotton shirt',
            'account_group' => ['id' => 1253, 'name' => 'Productos'],
            'type' => 'Product',
            'stock_control' => true,
            'active' => true,
            'tax_classification' => 'Taxed',
            'tax_included' => true,
            'tax_consumption_value' => 0,
            'taxes' => [['id' => 13156, 'name' => 'IVA 19%', 'type' => 'IVA', 'percentage' => 19]],
            'prices' => [['currency_code' => 'COP', 'price_list' => [['position' => 1, 'name' => 'Sale Price 1', 'value' => '1069.77']]]],
            'unit' => ['code' => '94', 'name' => 'Unidad'],
            'unit_label' => 'Unit',
            'reference' => 'REF1',
            'description' => 'This is a description',
            'additional_fields' => ['barcode' => 'B0123', 'brand' => 'Gef', 'tariff' => '1234567890', 'model' => 'Loiry'],
            'available_quantity' => 42,
            'warehouses' => [['id' => 1270, 'name' => 'Main Warehouse', 'quantity' => '42']],
            'metadata' => ['created' => '2020-06-15T03:33:17.0000000+00:00', 'last_updated' => null, 'stock_updated' => null],
        ]);

        $this->assertSame('497f6eca-6276-4993-bfeb-53cbbbba6f08', $product->id);
        $this->assertSame('Item-1', $product->code);
        $this->assertNotNull($product->accountGroup);
        $this->assertSame(1253, $product->accountGroup->id);
        $this->assertSame(ProductType::Product, $product->type);
        $this->assertSame(TaxClassification::Taxed, $product->taxClassification);

        $this->assertCount(1, $product->taxes);
        $this->assertSame('IVA', $product->taxes[0]->type);
        $this->assertSame(19.0, $product->taxes[0]->percentage);

        $this->assertCount(1, $product->prices);
        $this->assertSame('COP', $product->prices[0]->currencyCode);
        $this->assertSame(1069.77, $product->prices[0]->priceList[0]->value);
        $this->assertSame('Sale Price 1', $product->prices[0]->priceList[0]->name);

        $this->assertNotNull($product->unit);
        $this->assertSame('94', $product->unit->code);
        $this->assertSame('Unidad', $product->unit->name);

        $this->assertSame(42.0, $product->availableQuantity);
        $this->assertCount(1, $product->warehouses);
        $this->assertSame(42.0, $product->warehouses[0]->quantity);

        $this->assertNotNull($product->metadata);
        $this->assertSame('2020-06-15T03:33:17.0000000+00:00', $product->metadata->created);
    }

    public function test_from_array_maps_combo_components(): void
    {
        $product = Product::fromArray([
            'id' => 'abc',
            'type' => 'Combo',
            'components' => [
                ['id' => 'c1', 'code' => 'product-1', 'name' => 'Product One'],
                ['id' => 'c2', 'code' => 'product-2', 'name' => 'Product Two'],
            ],
        ]);

        $this->assertSame(ProductType::Combo, $product->type);
        $this->assertCount(2, $product->components);
        $this->assertSame('product-1', $product->components[0]->code);
    }

    public function test_from_array_tolerates_a_minimal_payload(): void
    {
        $product = Product::fromArray(['id' => 'abc']);

        $this->assertSame('abc', $product->id);
        $this->assertSame(ProductType::Product, $product->type);
        $this->assertNull($product->taxClassification);
        $this->assertNull($product->accountGroup);
        $this->assertNull($product->unit);
        $this->assertSame([], $product->taxes);
        $this->assertSame([], $product->warehouses);
        $this->assertNull($product->metadata);
    }
}
