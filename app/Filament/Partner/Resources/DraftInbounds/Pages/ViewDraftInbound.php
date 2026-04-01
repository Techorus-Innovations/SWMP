<?php

namespace App\Filament\Partner\Resources\DraftInbounds\Pages;

use App\Filament\Partner\Resources\DraftInbounds\DraftInboundResource;
use App\Models\DraftInbound;
use App\Models\InboundAttp;
use App\Models\WorkingHours;
use App\Services\CsvInboundProcessor;
use App\Services\SapService;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard\Step;
use Harris21\Fuse\CircuitBreaker;
use Illuminate\Support\Carbon;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class ViewDraftInbound extends ViewRecord
{
    protected static string $resource = DraftInboundResource::class;

    private $circuitBreaker;

    public function __construct()
    {
        $this->circuitBreaker = new CircuitBreaker('sap');
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('submitInbound')
                ->label('Submit Inbound')
                ->color('success')
                ->disabled(fn (): bool => $this->circuitBreaker->isOpen())
                ->tooltip(fn () => $this->circuitBreaker->isOpen() ? 'External service is currently unavailable' : null)
                ->steps([
                    Step::make('Receiving Details')
                        ->description('Choose receive date and time')
                        ->schema([
                            DateRangePicker::make('date')
                                ->displayFormat('YYYY-MM-DD')
                                ->disableRanges()
                                // ->firstDayOfWeek(0)
                                ->minDate(Carbon::now()->addDays(2))
                                ->maxDate(Carbon::now()->addMonth())
                                ->autoApply()
                                ->alwaysShowCalendar()
                                ->live()
                                ->singleCalendar()
                                ->required(),
                            Select::make('time_slot')
                                ->label('Select Time Slot')
                                ->disabled(fn (Get $get): bool => ! filled($get(path: 'date')))
                                ->options(
                                    function (Get $get) {
                                        if (! $get('date')) {
                                            return [];
                                        }

                                        $date = $get('date');
                                        $sapService = new SapService;
                                        $usedSlots = $sapService->getDeliverySlotsReservedCounts($date);

                                        $day = strtolower(Carbon::parse($date)->format('D'));

                                        $workingHours = WorkingHours::where('day', $day)->get();

                                        if (! $workingHours) {
                                            return [];
                                        }
                                        $slots = [];
                                        foreach ($workingHours as $hours) {
                                            $start = Carbon::parse($hours->start);
                                            $end = Carbon::parse($hours->end);
                                            while ($start < $end) {
                                                $hourKey = $start->format('H');
                                                $label = $start->format('h:i A');
                                                if (! isset($usedSlots[$hourKey]) || $usedSlots[$hourKey] < $hours->quantity) {
                                                    $slots[$hourKey] = $label;
                                                }

                                                $start->addHour();
                                            }
                                        }

                                        return $slots;
                                    }
                                )
                                ->live()
                                ->required(),
                        ]),
                    Step::make('Upload ATTP file')
                        ->description('Upload ATTP file')
                        ->hidden(function () {
                            return ! $this->record->materials()
                                ->where('attp_required', 'ZT')
                                ->exists();
                        })
                        ->schema([
                            FileUpload::make('csv_file')
                            // ->dehydrated(true)
                                ->label('Upload CSV File')
                                ->acceptedFileTypes(['text/csv', 'application/csv'])
                                ->maxSize(5120)
                                ->required()
                                ->rules([
                                    fn (): Closure => function (string $attribute, $value, Closure $fail) {
                                        $filePath = $value->getPathname();

                                        if (file_exists($filePath)) {
                                            $processor = new CsvInboundProcessor;
                                            $results = $processor->processFile($filePath);
                                            // dd($results);
                                        }
                                        $attpGtins = $this->record->materials()->where('attp_required', 'ZT')->pluck('gtin_code')->toArray();
                                        // dd($attpGtins);
                                        $missingGtins = [];
                                        foreach ($attpGtins as $gtin) {
                                            if (! in_array(substr($gtin, 0, -1), $results)) {
                                                $missingGtins[] = substr($gtin, 0, -1);
                                            }
                                        }
                                        if (count($missingGtins) > 0) {
                                            $fail('ATTP file missing Gtins: '.implode(', ', $missingGtins));
                                        } else {

                                            $sapService = new SapService;
                                            $sapService->uploadAttpFile($filePath);
                                        }
                                    },
                                ])
                                ->columnSpanFull(),

                        ]),
                ])
                ->action(function (array $data) {
                    $date = $data['date'];
                    $time = $data['time_slot'];
                    $bpcode = filament()->auth()->user()->businessPartner->bp_code;
                    $contractId = $this->record->contract_id;
                    $materialCodesWithQuantity = $this->record->getMaterialCodesWithQuantity();

                    // dd($materialCodesWithQuantity);
                    $sapService = new SapService;
                    $response = $sapService->createInbound($bpcode, $contractId, $materialCodesWithQuantity, $date, $time);
                    preg_match('/Inbound Request(\d+)/', $response['d']['Message'], $matches);
                    $requestNumber = $matches[1] ?? null;
                    if ($requestNumber != null && isset($data['csv_file'])) {
                        InboundAttp::create([
                            'inbound_number' => $requestNumber,
                            'attp_file' => $data['csv_file'],
                        ]);
                    }
                    DraftInbound::where('id', $this->record->id)->delete();
                    Notification::make()
                        ->title('Success')
                        ->body('Inbound created successfully with id: '.$requestNumber.'.')
                        ->success()
                        ->send();
                    redirect()->route('filament.partner.pages.my-inbounds');
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['materials'] = $this->record->materials
            ->map(fn ($material) => [
                'material_id' => $material->id,
                'quantity' => $material->pivot->quantity,
            ])
            ->toArray();

        return $data;
    }
}
