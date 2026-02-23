<?php

namespace App\Filament\Partner\Pages\Auth;

use Filament\Pages\Page;

class PendingApproval extends Page
{
    protected string $view = 'filament.partner.pages.auth.pending-approval';

    protected static ?string $title = 'Pending Approval';

    protected static ?string $slug = 'pending-approval';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function getLayout(): string
    {
        return 'filament-panels::components.layout.simple';
    }
}
