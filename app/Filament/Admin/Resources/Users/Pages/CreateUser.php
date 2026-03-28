<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Enums\UserTypeEnum;
use App\Filament\Admin\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $user = $this->record;
        
        $roleName = ($user->type === UserTypeEnum::Admin) ? 'super_admin' : 'partner';
        $user->assignRole($roleName);
    }
}
