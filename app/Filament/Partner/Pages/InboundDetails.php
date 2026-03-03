<?php

namespace App\Filament\Partner\Pages;

use App\Services\SapService;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;

class InboundDetails extends Page implements HasTable
{
    use InteractsWithTable;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.partner.pages.inbound-details';

    /** Populated from URL query parameter */
    #[Url]
    public string $inboundRequestId = '';

    protected $sapService;

    private ?array $response = null;

    public static function canAccess(): bool
    {
        return filament()->auth()->user()->canViewInbounds();
    }

    public function boot(): void
    {
        $this->sapService = new SapService;
        $this->response = $this->sapService->getInboundRequestById(
            filament()->auth()->user()->businessPartner->bp_code,
            $this->inboundRequestId
        );
    }

    public function getTitle(): string
    {
        return __('Inbound Request') . ' #' . $this->inboundRequestId;
    }

    public function infolist(Schema $schema): Schema
    {
        $record = $this->response;

        return $schema->components([
            Section::make(__('Inbound Request Details'))
                ->columns(3)
                ->schema([
                    TextEntry::make('inbound_request_id')
                        ->label(__('Request ID'))
                        ->state($record['inbound_request_id'] ?? '—'),
                    TextEntry::make('created_date')
                        ->label(__('Created Date'))
                        ->state($record['created_date'] ?? '—'),
                    TextEntry::make('shipping_indicator')
                        ->label(__('Shipping Indicator'))
                        ->state($record['shipping_indicator'] ?? '—')
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function () {
                $items = collect($this->response['items'] ?? [])->toArray();
                $currentPage = $this->getTablePage();
                $perPage = $this->getTableRecordsPerPage() ?? 10;

                $paginated = array_slice($items, ($currentPage - 1) * $perPage, $perPage);

                return new LengthAwarePaginator(
                    $paginated,
                    count($items),
                    $perPage,
                    $currentPage
                );
            })
            ->columns([
                TextColumn::make('material_code')
                    ->label('Material Code'),
                TextColumn::make('material_description')
                    ->label('Material Description'),
                TextColumn::make('quantity')
                    ->label('Quantity'),
                TextColumn::make('measuring_unit')
                    ->label('Unit'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color('primary'),
            ])
            ->paginated([10, 25, 50])
            ->filters([])
            ->actions([])
            ->bulkActions([]);
    }
}
