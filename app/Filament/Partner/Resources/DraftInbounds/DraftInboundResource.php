<?php

namespace App\Filament\Partner\Resources\DraftInbounds;

use App\Filament\Partner\Resources\DraftInbounds\Pages\CreateDraftInbound;
use App\Filament\Partner\Resources\DraftInbounds\Pages\EditDraftInbound;
use App\Filament\Partner\Resources\DraftInbounds\Pages\ListDraftInbounds;
use App\Filament\Partner\Resources\DraftInbounds\Pages\ViewDraftInbound;
use App\Filament\Partner\Resources\DraftInbounds\Schemas\DraftInboundForm;
use App\Filament\Partner\Resources\DraftInbounds\Tables\DraftInboundsTable;
use App\Models\DraftInbound;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use App\Filament\Partner\Resources\DraftInbounds\RelationManagers\MaterialsRelationManager;

class DraftInboundResource extends Resource
{
    protected static ?string $model = DraftInbound::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|\UnitEnum|null $navigationGroup = 'Inbounds';

    protected static ?string $navigationLabel = 'Draft Inbounds';

    public static function canAccess(): bool
    {
        return filament()->auth()->user()->canCreateInbounds();
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) DraftInbound::where('user_id', auth()->id())->count() ?: 0;
    }

    public static function form(Schema $schema): Schema
    {
        return DraftInboundForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DraftInboundsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            MaterialsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListDraftInbounds::route('/'),
            'create' => CreateDraftInbound::route('/create'),
            'edit'   => EditDraftInbound::route('/{record}/edit'),
            'view'   => ViewDraftInbound::route('/{record}'),
        ];
    }
}
