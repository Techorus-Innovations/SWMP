<?php

namespace App\Filament\Partner\Pages\Auth;

use Filament\Pages\Page;

class RejectedRequest extends Page
{
    protected string $view = 'filament.partner.pages.auth.rejected-request';

    protected static ?string $title = 'Request Rejected';

    protected static ?string $slug = 'rejected-request';

    /**
     * Hide this page from the navigation sidebar.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function getLayout(): string
    {
        return 'filament-panels::components.layout.simple';
    }
}
