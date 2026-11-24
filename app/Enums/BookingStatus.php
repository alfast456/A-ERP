<?php

namespace App\Enums;

enum BookingStatus: string
{
    case HOLD = 'HOLD';
    case CONFIRMED_TO_ORDER = 'CONFIRMED_TO_ORDER';
    case EXPIRED = 'EXPIRED';
    case CANCELLED = 'CANCELLED';
}
