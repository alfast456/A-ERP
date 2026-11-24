<?php

namespace App\Enums;

enum OrderStatus: string
{
    case DRAFT = 'DRAFT';
    case CONFIRMED = 'CONFIRMED';
    case IN_PLANNING = 'IN_PLANNING';
    case IN_PRODUCTION = 'IN_PRODUCTION';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';
}
