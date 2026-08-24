<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Enums;

/**
 * `products.type`. Siigo's own documentation is inconsistent about the
 * closed set across pages (creation lists `Product`/`Service`/`Combo`,
 * update lists `Product`/`Service`/`ConsumerGood`, and both pages'
 * prose description omits `Combo` while an example payload uses it) —
 * see docs/research/siigo-api-co/02-products.md. All four values are
 * documented somewhere officially, so all four are modeled here.
 * `Combo` requires Siigo Nube Premium.
 */
enum ProductType: string
{
    case Product = 'Product';
    case Service = 'Service';
    case ConsumerGood = 'ConsumerGood';
    case Combo = 'Combo';
}
