<?php

namespace App\Enum;

enum ItemVoucherType: string
{
    case ABSOLUTE = 'absolute';
    case PERCENT = 'percent';
}