<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Enums;

use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\DetailedPaymentReceiptData;

/**
 * `POST /v1/payment-receipts` -> `type`. `DebtPayment` pays down one or
 * more purchase invoice dues (requires `items[]`), `AdvancePayment` is
 * an advance to a supplier with no invoice reference (no `items[]`),
 * and `Detailed` posts the receipt as an explicit set of accounting
 * entries — see
 * {@see DetailedPaymentReceiptData}.
 * See docs/research/siigo-api-co/09-payment-receipts.md.
 */
enum PaymentReceiptType: string
{
    case DebtPayment = 'DebtPayment';
    case AdvancePayment = 'AdvancePayment';
    case Detailed = 'Detailed';
}
