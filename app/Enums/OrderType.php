<?php

namespace App\Enums;

enum OrderType: string
{
    case BULK = 'BULK';
    case SAMPLE = 'SAMPLE';
    case REMAKE = 'REMAKE';
    case PROMO = 'PROMO';
}
