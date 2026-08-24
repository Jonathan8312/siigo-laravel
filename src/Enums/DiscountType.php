<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Enums;

/**
 * `document-types[].discount_type` — confirmed as a closed set on the
 * "Consultar Tipos de Facturas de venta" page (2026-08-23): determines
 * whether an item's discount is sent as a percentage or an absolute
 * value on that document type.
 */
enum DiscountType: string
{
    case Percentage = 'Percentage';
    case Value = 'Value';
}
