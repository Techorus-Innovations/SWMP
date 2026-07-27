<?php

namespace App\Enums;

enum UserTypeEnum: string
{
    case Admin = 'A';
    case Customer = 'C';
    case Supplier = 'S';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Customer => 'Customer',
            self::Supplier => 'Supplier',
        };
    }
}
