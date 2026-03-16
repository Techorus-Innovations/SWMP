<?php

namespace App\Filament\Partner\Pages;

use App\Services\SapService;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;

class MyOutbounds extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static string|\UnitEnum|null $navigationGroup = 'Outbounds';

    protected static ?string $navigationLabel = 'My Outbounds';

    protected string $view = 'filament.partner.pages.my-outbounds';

    protected $sapService;

    public static function canAccess(): bool
    {
        return filament()->auth()->user()->canViewOutbounds();
    }

    public function boot(): void
    {
        $this->sapService = new SapService;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function () {
                $currentPage = $this->getTablePage();
                $perPage = $this->getTableRecordsPerPage() ?? 10;
                $response = $this->sapService->getOutboundRequests(
                    filament()->auth()->user()->businessPartner->bp_code,
                    $perPage,
                    $currentPage
                );

                return new LengthAwarePaginator(
                    $response['paginatedOutbounds'],
                    $response['totalCount'],
                    $perPage,
                    $currentPage
                );
            })
            ->columns([
                TextColumn::make('outbound_request_id')
                    ->label(__('Outbound Request ID')),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge(),
                IconColumn::make('ship_to_party')
                    ->label(__('Shipping Included'))
                    ->state(fn ($record) => $record['ship_to_party'] != filament()->auth()->user()->businessPartner->bp_code)
                    ->boolean(),
                TextColumn::make('delivery_date')
                    ->label(__('Delivery Date')),
                TextColumn::make('delivery_time')
                    ->label(__('Delivery Time')),
            ])
            ->paginated([10, 25, 50])
            ->filters([])
            ->actions([
                Action::make('view')
                    ->label('View Items')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => OutboundDetails::getUrl([
                        'outboundRequestId' => $record['outbound_request_id'],
                    ])),
            ])
            ->bulkActions([]);
    }
}
