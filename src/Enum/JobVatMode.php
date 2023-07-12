<?php

namespace App\Enum;

enum JobVatMode: int
{
    // there is no vat at all that applies to that order
    case VAT_NONE = 0;
    // vat excluded means that all positions do not include vat and so it's applied to the subtotal
    case VAT_EXCLUDED = 1;
    // vat included means that all positions do include vat already and so no extra vat is applied to subtotal
    case VAT_INCLUDED = 2;
}