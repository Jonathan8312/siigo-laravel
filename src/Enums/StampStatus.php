<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Enums;

/**
 * `invoices.stamp.status` — documented as a closed set: whether the
 * electronic invoice has been sent to the DIAN and, if so, the
 * outcome.
 */
enum StampStatus: string
{
    case Draft = 'Draft';
    case Accepted = 'Accepted';
    case Rejected = 'Rejected';
}
