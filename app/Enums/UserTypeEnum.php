<?php

namespace App\Enums;

enum UserTypeEnum: string
{
    case Admin = 'a';
    case Customer = 'c';
    // case Supplier = 's';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Customer => 'Customer',
            // self::Supplier => 'Supplier',
        };
    }
}
