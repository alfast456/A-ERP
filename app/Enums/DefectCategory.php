<?php

namespace App\Enums;

enum DefectCategory: string
{
    case KNITTING_NEEDLE = 'KNITTING_NEEDLE';
    case YARN_DEFECT = 'YARN_DEFECT';
    case MACHINE_TIMING = 'MACHINE_TIMING';
    case DIRT_OIL = 'DIRT_OIL';
    case LINKING_DEFECT = 'LINKING_DEFECT';
    case OTHER = 'OTHER';
}
