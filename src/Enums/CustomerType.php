<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Enums;

/**
 * `customers.type` — documented as a closed set: a customer/third
 * party, a supplier, or neither.
 */
enum CustomerType: string
{
    case Customer = 'Customer';
    case Supplier = 'Supplier';
    case Other = 'Other';
}
