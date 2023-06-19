<?php

namespace App\Enum;

enum JobVatMode: int
{
    case VAT_NONE = 0;
    case VAT_DEFAULT = 1;
    case VAT_SPECIAL = 2;
}