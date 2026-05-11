<?php

namespace App\Enums;

enum BusinessPartnerTypeEnum: string
{
    case Supplier = 's';
    case Customer = 'c';

    public function label(): string
    {
        return match ($this) {
            self::Supplier => 'Supplier',
            self::Customer => 'Customer',
        };
    }
}
