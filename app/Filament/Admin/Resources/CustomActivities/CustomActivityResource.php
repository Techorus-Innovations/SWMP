<?php

namespace App\Filament\Admin\Resources\CustomActivities;

use App\Filament\Resources\CustomActivities\Pages\CreateCustomActivity;
use App\Filament\Resources\CustomActivities\Pages\EditCustomActivity;
use App\Filament\Resources\CustomActivities\Pages\ListCustomActivities;
use App\Filament\Resources\CustomActivities\Schemas\CustomActivityForm;
use App\Filament\Resources\CustomActivities\Tables\CustomActivitiesTable;
use App\Models\CustomActivity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Jacobtims\FilamentLogger\Resources\ActivityResource;
use Jacobtims\FilamentLogger\Resources\ActivityResource\Schemas\CustomActivityInfolist;

class CustomActivityResource extends ActivityResource
{
    public static function infolist(Schema $schema): Schema
    {
        return CustomActivityInfolist::configure($schema);
    }
}
