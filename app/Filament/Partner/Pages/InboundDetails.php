<?php

namespace App\Filament\Partner\Pages;

use App\Models\Material;
use App\Services\SapService;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
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
        $this->response = $this->sapService->getDetailedInboundRequest(
            filament()->auth()->user()->businessPartner->bp_code,
            $this->inboundRequestId
        );
    }

    public function getTitle(): string
    {
        return __('Inbound Request').' #'.$this->inboundRequestId;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cancelInbound')
                ->label(__('Cancel Inbound'))
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->modalHeading(__('Cancel Inbound Request'))
                ->modalDescription(__('Are you sure you want to cancel this inbound request? This action cannot be undone.'))
                ->hidden(function () {
                    if (! filament()->auth()->user()->canCancelInbounds()) {
                        return true;
                    }

                    if (isset($this->response['delivery_date'])) {
                        if (\Carbon\Carbon::parse($this->response['delivery_date'])->startOfDay()->lessThan(today())) {
                            return true;
                        }
                    }

                    if (isset($this->response['items'])) {
                        foreach ($this->response['items'] as $item) {
                            $received = (float) ($item['received_quantity'] ?? $item['recieved_quantity'] ?? 0);
                            if ($received > 0) {
                                return true;
                            }

                            if ($item['received_quantity'] == 0 && $item['delivery_status_code'] == '9') {
                                return true;
                            }
                        }
                    }

                    return false;
                })
                ->action(function () {
                    $sapService = new SapService;
                    $status = $sapService->cancelInboundRequestBatch($this->response['inbound_request_id']);
                    if ($status == true) {
                        $this->boot();
                        Notification::make()
                            ->title(__('Inbound Cancelled'))
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title(__('Something went wrong, please try again later'))
                            ->danger()
                            ->send();
                    }

                }),
        ];
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
                        ->state($record['shipping_indicator'] ?? '—'),
                    TextEntry::make('delivery_date')
                        ->label(__('Delivery Date'))
                        ->state($record['delivery_date'] ?? '—'),
                    TextEntry::make('delivery_time')
                        ->label(__('Delivery Time'))
                        ->state($record['delivery_time'] ?? '—'),
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

                $enrichedInboundMaterial = collect($paginated ?? [])->map(function ($inboundMaterial) {
                    $material = Material::where('code', $inboundMaterial['material_code'])->first();
                    $inboundMaterial['material_name_en'] = $material?->name_en;
                    $inboundMaterial['material_name_ar'] = $material?->name_ar;
                    $inboundMaterial['gtin'] = $material?->gtin_code ?? null;

                    return $inboundMaterial;
                })->toArray();

                return new LengthAwarePaginator(
                    $enrichedInboundMaterial,
                    count($items),
                    $perPage,
                    $currentPage
                );
            })
            ->columns([
                TextColumn::make('material_code')
                    ->label('Material Code'),
                TextColumn::make('material_name_en')
                    ->label('Material Name (EN)'),
                TextColumn::make('material_name_ar')
                    ->label('Material Name (AR)'),
                TextColumn::make('gtin')
                    ->label('GTIN Code'),
                TextColumn::make('quantity')
                    ->label('Quantity'),
                TextColumn::make('measuring_unit')
                    ->label('Unit'),
                TextColumn::make('received_quantity')
                    ->label('Received Quantity'),
                TextColumn::make('received_unit')
                    ->label('Received Unit'),
                TextColumn::make('delivery_status_code')
                    ->label('Status')
                    ->formatStateUsing(function ($state, $record) {
                        $received = (float) ($record['received_quantity'] ?? 0);
                        $quantity = (float) ($record['quantity'] ?? 0);

                        if ($state == '9' && $received == 0) {
                            return __('Cancelled');
                        } elseif ($state == '9' && $received < $quantity) {
                            return __('Partially received');
                        } elseif ($state == '9' && $received == $quantity) {
                            return __('Completely received');
                        } elseif ($state == '1') {
                            return __('Not yet received');
                        }

                        return $state;
                    })
                    ->badge()
                    ->color('primary'),
            ])
            ->paginated([10, 25, 50])
            ->filters([])
            ->actions([])
            ->bulkActions([]);
    }
}
