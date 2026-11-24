<?php

namespace App\Enums;

enum ReworkStatus: string
{
    case IN_REPAIR = 'IN_REPAIR';
    case RESOLVED = 'RESOLVED';
    case SCRAPPED = 'SCRAPPED';
}
