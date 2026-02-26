<?php

namespace App\Filament\Partner\Pages;

use App\Services\SapService;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;

class MyInbounds extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static string|\UnitEnum|null $navigationGroup = 'Inbounds';

    protected static ?string $navigationLabel = 'My Inbounds';

    protected string $view = 'filament.partner.pages.my-inbounds';

    protected $sapService;

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
                $response = $this->sapService->getInboundRequests(
                    filament()->auth()->user()->businessPartner->bp_code,
                    $perPage,
                    $currentPage
                );
                return new LengthAwarePaginator(
                    $response['paginatedInbounds'],
                    $response['totalCount'],
                    $perPage,
                    $currentPage
                );
            })
            ->columns([
                TextColumn::make('inbound_request_id')
                    ->label('Inbound Request ID'),
                TextColumn::make('created_date')
                    ->label('Created Date'),
                TextColumn::make('shipping_indicator')
                    ->label('Shipping Indicator')
            ])
            ->paginated([10, 25, 50])
            ->filters([])
            ->actions([
                Action::make('view')
                    ->label('View Items')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => InboundDetails::getUrl([
                        'inboundRequestId' => $record['inbound_request_id'],
                    ])),
            ])
            ->bulkActions([]);
    }
}
