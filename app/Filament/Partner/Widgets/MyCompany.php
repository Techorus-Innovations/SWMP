<?php

namespace App\Filament\Partner\Widgets;

use Filament\Widgets\Widget;

class MyCompany extends Widget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = false;
    protected string $view = 'filament.partner.widgets.my-company';
}
