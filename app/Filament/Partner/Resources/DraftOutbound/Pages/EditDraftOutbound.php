<?php

namespace App\Filament\Partner\Resources\DraftOutbound\Pages;

use App\Filament\Partner\Resources\DraftOutbound\DraftOutboundResource;
use App\Models\DraftOutbound;
use App\Models\WorkingHours;
use App\Services\SapService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Utilities\Get;
use Harris21\Fuse\CircuitBreaker;
use Livewire\Attributes\On;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class EditDraftOutbound extends EditRecord
{
    protected static string $resource = DraftOutboundResource::class;

    private $circuitBreaker;

    public function __construct()
    {
        $this->circuitBreaker = new CircuitBreaker('sap');
    }

    #[On('materials-updated')]
    public function refreshHeaderActions(): void
    {
        $this->record->refresh();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submitOutbound')
                ->label('Submit Outbound')
                ->color('success')
                ->hidden(fn ($record): bool => count($record->materials) == 0 || $record->materials->sum('quantity') == 0)
                ->disabled(fn ($record): bool => $this->circuitBreaker->isOpen())
                ->tooltip(fn () => $this->circuitBreaker->isOpen() ? 'External service is currently unavailable' : null)
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
                ])
                ->action(function (array $data) {
                    $date = $data['date'];
                    $time = $data['time_slot'];
                    $bpcode = filament()->auth()->user()->businessPartner->bp_code;
                    $outboundId = $this->record->id;

                    $sapService = new SapService;
                    $response = $sapService->createOutbound($bpcode, $outboundId, $date, $time);
                    DraftOutbound::where('id', $outboundId)->delete();
                    Notification::make()
                        ->title('Success')
                        ->body('Outbound created successfully.')
                        ->success()
                        ->send();
                    redirect()->route('filament.partner.pages.my-outbounds');
                }),
        ];
    }
}
