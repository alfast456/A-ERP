<?php

namespace App\Enums;

enum YarnTransactionType: string
{
    case RECEIPT_FROM_WH = 'RECEIPT_FROM_WH';
    case RETURN_TO_WH = 'RETURN_TO_WH';
    case USAGE_KNITTING = 'USAGE_KNITTING';
    case ADJUSTMENT = 'ADJUSTMENT';
}
