<?php

namespace App\Enums;

enum PartnerTypeEnum: string
{
    case Customer = 'c';
    case Supplier = 's';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Customer',
            self::Supplier => 'Supplier',
        };
    }
}
