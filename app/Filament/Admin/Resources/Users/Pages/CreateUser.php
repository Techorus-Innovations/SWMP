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
        if ($user->type !== UserTypeEnum::Admin) {
            $user->assignRole('partner');
        }
    }
}
