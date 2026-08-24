<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Enums;

/**
 * `items[].account.movement` on a `Detailed` payment receipt — whether
 * the accounting entry line is a debit or a credit.
 */
enum AccountMovement: string
{
    case Debit = 'Debit';
    case Credit = 'Credit';
}
