<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\DataTransferObjects\Products;

use Jonathan8312\Siigo\DataTransferObjects\Products\AdditionalFields;
use Jonathan8312\Siigo\DataTransferObjects\Products\ComboComponent;
use Jonathan8312\Siigo\DataTransferObjects\Products\PriceListEntry;
use Jonathan8312\Siigo\DataTransferObjects\Products\ProductData;
use Jonathan8312\Siigo\DataTransferObjects\Products\ProductPrice;
use Jonathan8312\Siigo\DataTransferObjects\Products\ProductTax;
use Jonathan8312\Siigo\Enums\ProductType;
use PHPUnit\Framework\TestCase;

final class ProductDataTest extends TestCase
{
    public function test_to_array_matches_the_documented_standard_product_shape(): void
    {
        $product = new ProductData(
            code: 'Item-1',
            name: 'Cotton shirt',
            accountGroup: 1253,
            type: ProductType::Product,
            taxes: [new ProductTax(id: 13156)],
            prices: [new ProductPrice('COP', [new PriceListEntry(position: 1, value: 1069.77)])],
            unit: '94',
            unitLabel: 'Unit',
            reference: 'REF1',
            description: 'This is a description',
            additionalFields: new AdditionalFields(barcode: 'B0123', brand: 'Gef', tariff: '1234567890', model: 'Loiry'),
        );

        $this->assertSame([
            'code' => 'Item-1',
            'name' => 'Cotton shirt',
            'account_group' => 1253,
            'type' => 'Product',
            'stock_control' => false,
            'active' => true,
            'tax_classification' => 'Taxed',
            'tax_included' => false,
            'taxes' => [['id' => 13156]],
            'prices' => [['currency_code' => 'COP', 'price_list' => [['position' => 1, 'value' => 1069.77]]]],
            'unit' => '94',
            'unit_label' => 'Unit',
            'reference' => 'REF1',
            'description' => 'This is a description',
            'additional_fields' => ['barcode' => 'B0123', 'brand' => 'Gef', 'tariff' => '1234567890', 'model' => 'Loiry'],
        ], $product->toArray());
    }

    public function test_to_array_matches_the_documented_combo_shape(): void
    {
        $product = new ProductData(
            code: '1234',
            name: 'Combo de prueba',
            accountGroup: 121,
            type: ProductType::Combo,
            components: [new ComboComponent('product-1', 100), new ComboComponent('product-2', 20)],
        );

        $array = $product->toArray();

        $this->assertSame('Combo', $array['type']);
        $this->assertSame([
            ['code' => 'product-1', 'quantity' => 100.0],
            ['code' => 'product-2', 'quantity' => 20.0],
        ], $array['components']);
    }

    public function test_to_array_omits_optional_fields_when_not_set(): void
    {
        $product = new ProductData(code: 'X1', name: 'Minimal', accountGroup: 1);

        $array = $product->toArray();

        $this->assertArrayNotHasKey('tax_consumption_value', $array);
        $this->assertArrayNotHasKey('taxes', $array);
        $this->assertArrayNotHasKey('prices', $array);
        $this->assertArrayNotHasKey('unit', $array);
        $this->assertArrayNotHasKey('unit_label', $array);
        $this->assertArrayNotHasKey('reference', $array);
        $this->assertArrayNotHasKey('description', $array);
        $this->assertArrayNotHasKey('additional_fields', $array);
        $this->assertArrayNotHasKey('components', $array);
    }
}
