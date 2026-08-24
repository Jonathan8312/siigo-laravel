<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Enums;

/**
 * `credit-notes.items[].taxpayer` — required whenever the item's
 * `price` is `0` (a gift/obsequio line), stating who bears the VAT.
 * Confirmed as a closed set on the "Crear Nota Crédito" page
 * (2026-08-23), "Notas crédito con productos de obsequio" section.
 */
enum CreditNoteTaxpayer: string
{
    case Customer = 'Customer';
    case Company = 'Company';
}
