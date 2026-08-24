<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Enums;

/**
 * `products.tax_classification`. Siigo's creation/update pages document
 * the English values used here (`Taxed`, `Exempt`, `Excluded`); the
 * read-only "Consultar Producto" page describes the same field using
 * Spanish terms (`Gravado`, `Exento`, `Excluido`) instead — not
 * confirmed against a real response, so this enum follows the
 * write-path (creation/update) terminology. See
 * docs/research/siigo-api-co/02-products.md.
 */
enum TaxClassification: string
{
    case Taxed = 'Taxed';
    case Exempt = 'Exempt';
    case Excluded = 'Excluded';
}
