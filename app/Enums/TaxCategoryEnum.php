<?php

namespace App\Enums;

enum TaxCategoryEnum: string
{
    case TaxExempt = '0';
    case LiableForTaxes = '1';

    public function label(): string
    {
        return match ($this) {
            self::TaxExempt => 'Tax Exempt',
            self::LiableForTaxes => 'Liable for Taxes',
        };
    }
}
