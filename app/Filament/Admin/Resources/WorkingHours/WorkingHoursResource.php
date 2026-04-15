<?php

namespace App\Filament\Admin\Resources\WorkingHours;

use App\Filament\Admin\Resources\WorkingHours\Pages\CreateWorkingHours;
use App\Filament\Admin\Resources\WorkingHours\Pages\EditWorkingHours;
use App\Filament\Admin\Resources\WorkingHours\Pages\ListWorkingHours;
use App\Filament\Admin\Resources\WorkingHours\Schemas\WorkingHoursForm;
use App\Filament\Admin\Resources\WorkingHours\Tables\WorkingHoursTable;
use App\Models\WorkingHours;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WorkingHoursResource extends Resource
{
    protected static ?string $model = WorkingHours::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    public static function form(Schema $schema): Schema
    {
        return WorkingHoursForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkingHoursTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkingHours::route('/'),
            'create' => CreateWorkingHours::route('/create'),
        ];
    }
}
