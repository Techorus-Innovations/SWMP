<?php
namespace App\Filament\Partner\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;
use App\Filament\Partner\Widgets\MyCompany;
class Dashboard extends BaseDashboard
{
    // Customize your dashboard here (e.g., change the title or icon)
    // protected static ?string $navigationIcon = 'heroicon-o-home';
    
    public function getWidgets(): array
    {
        return [
            // AccountWidget::class,
            MyCompany::class,
            ];
    }

}
