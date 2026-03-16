<?php

namespace App\Filament\Partner\Resources\DraftOutbound;

use App\Filament\Partner\Resources\DraftOutbound\Pages\CreateDraftOutbound;
use App\Filament\Partner\Resources\DraftOutbound\Pages\EditDraftOutbound;
use App\Filament\Partner\Resources\DraftOutbound\Pages\ListDraftOutbounds;
use App\Filament\Partner\Resources\DraftOutbound\RelationManagers\DraftOutboundMaterialsRelationManager;
use App\Filament\Partner\Resources\DraftOutbound\Schemas\DraftOutboundForm;
use App\Filament\Partner\Resources\DraftOutbound\Tables\DraftOutboundsTable;
use App\Models\DraftOutbound;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DraftOutboundResource extends Resource
{
    protected static ?string $model = DraftOutbound::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|\UnitEnum|null $navigationGroup = 'Outbounds';

    protected static ?string $navigationLabel = 'Draft Outbounds';

    public static function canAccess(): bool
    {
        return filament()->auth()->user()->canCreateOutbounds();
    }

    public static function canCreate(): bool
    {
        // Check if user already has an existing draft
        return !DraftOutbound::where('business_partner_id', filament()->auth()->user()->business_partner_id)
            ->exists();
    }

    public static function form(Schema $schema): Schema
    {
        return DraftOutboundForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DraftOutboundsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DraftOutboundMaterialsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDraftOutbounds::route('/'),
            'create' => CreateDraftOutbound::route('/create'),
            'edit' => EditDraftOutbound::route('/{record}/edit'),
        ];
    }
}
