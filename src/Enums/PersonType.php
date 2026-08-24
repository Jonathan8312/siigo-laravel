<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Enums;

/**
 * `customers.person_type` — documented as a closed set: a natural
 * person or a company/legal entity.
 */
enum PersonType: string
{
    case Person = 'Person';
    case Company = 'Company';
}
