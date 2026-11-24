<?php

namespace App\Enums;

enum WipStatus: string
{
    case DISPATCHED = 'DISPATCHED';
    case RECEIVED = 'RECEIVED';
    case DISCREPANCY = 'DISCREPANCY';
}
