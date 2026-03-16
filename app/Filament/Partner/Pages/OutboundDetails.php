<?php

namespace App\Filament\Partner\Pages;

use App\Services\SapService;
use Filament\Infolists\Components\IconEntry;
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

class OutboundDetails extends Page implements HasTable
{
    use InteractsWithTable;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.partner.pages.outbound-details';

    /** Populated from URL query parameter */
    #[Url]
    public string $outboundRequestId = '';

    protected $sapService;

    private ?array $response = null;

    public static function canAccess(): bool
    {
        return filament()->auth()->user()->canViewOutbounds();
    }

    public function boot(): void
    {
        $this->sapService = new SapService;
        $this->response = $this->sapService->getOutboundRequestById(
            filament()->auth()->user()->businessPartner->bp_code,
            $this->outboundRequestId
        );
    }

    public function getTitle(): string
    {
        return __('Outbound Request').' #'.$this->outboundRequestId;
    }

    public function infolist(Schema $schema): Schema
    {
        $record = $this->response;

        return $schema->components([
            Section::make(__('Outbound Request Details'))
                ->columns(3)
                ->schema([
                    TextEntry::make('outbound_request_id')
                        ->label(__('Request ID'))
                        ->state($record['outbound_request_id'] ?? '—'),
                    TextEntry::make('status')
                        ->label(__('status'))
                        ->state($record['status'] ?? '—'),
                    TextEntry::make('delivery_time')
                        ->label(__('Delivery Time'))
                        ->state($record['delivery_time'] ?? '—'),
                    TextEntry::make('delivery_date')
                        ->label(__('Delivery Date'))
                        ->state($record['delivery_date'] ?? '—'),
                    IconEntry::make('ship_to_party')
                        ->label(__('Shipping Included'))
                        ->state($record['ship_to_party'] != filament()->auth()->user()->businessPartner->bp_code)
                        ->boolean()
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
                // TextColumn::make('area_code')
                //     ->label('Area Code'),
                TextColumn::make('inbound_request_reference')
                    ->label('Inbound Request Reference')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => InboundDetails::getUrl([
                        'inboundRequestId' => $record['inbound_request_reference'],
                    ])),
            ])
            ->paginated([10, 25, 50])
            ->filters([])
            ->actions([])
            ->bulkActions([]);
    }
}
