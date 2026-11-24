<?php

namespace App\Enums;

enum DepartmentType: string
{
    case KNITTING = 'KNITTING';
    case LINKING = 'LINKING';
    case SETTING = 'SETTING';
    case PACKING = 'PACKING';
    case WAREHOUSE = 'WAREHOUSE';
    case QC = 'QC';
}
