<?php

namespace App\Enums;

enum MachineStatus: string
{
    case RUNNING = 'RUNNING';
    case IDLE = 'IDLE';
    case BREAKDOWN = 'BREAKDOWN';
    case MAINTENANCE = 'MAINTENANCE';
    case OFFLINE = 'OFFLINE';
}
