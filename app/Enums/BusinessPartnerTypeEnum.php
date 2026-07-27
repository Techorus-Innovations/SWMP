<?php

namespace App\Enums;

enum BusinessPartnerTypeEnum: string
{
    case Supplier = 'S';
    case Customer = 'C';

    public function label(): string
    {
        return match ($this) {
            self::Supplier => 'Supplier',
            self::Customer => 'Customer',
        };
    }
}
