<?php

namespace App\Enums;

/** A line on a quotation: one of the vendor's packages, or an add-on typed in. */
enum QuotationItemKind: string
{
    case Package = 'package';
    case Addon = 'addon';

    public function label(): string
    {
        return __('enums.quotation_item_kind.'.$this->value);
    }
}
