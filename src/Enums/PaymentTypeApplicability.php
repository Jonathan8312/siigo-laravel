<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Enums;

/**
 * Which side of a transaction a payment type applies to, as documented
 * for `GET /v1/payment-types`: `Cartera` (accounts receivable — sales),
 * `Proveedor` (accounts payable — purchases), or both. Kept in Siigo's
 * own Spanish terms rather than translated, since the API returns these
 * exact literal values.
 */
enum PaymentTypeApplicability: string
{
    case Cartera = 'Cartera';
    case Proveedor = 'Proveedor';
    case CarteraProveedor = 'Cartera/Proveedor';
}
