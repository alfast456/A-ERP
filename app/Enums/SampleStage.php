<?php

namespace App\Enums;

enum SampleStage: string
{
    case PROTO = 'PROTO';
    case FIT = 'FIT';
    case PRE_PRODUCTION = 'PRE_PRODUCTION';
    case PILOT = 'PILOT';
}
