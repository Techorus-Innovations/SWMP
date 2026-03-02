<?php

namespace App\Filament\Partner\Resources\DraftInbounds\Pages;

use App\Filament\Partner\Resources\DraftInbounds\DraftInboundResource;
use App\Models\WorkingHours;
use App\Services\SapService;
use DB;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Carbon;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class ViewDraftInbound extends ViewRecord
{
    protected static string $resource = DraftInboundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submitInbound')
                ->label('Submit Inbound')
                ->color('success')
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
                                if (!$get('date')) {
                                    return [];
                                }
                               
                                $date = $get('date');
                                $sapService = new SapService();
                                $usedSlots = $sapService->getDeliverySlotsReservedCounts($date);

                                $day = strtolower(Carbon::parse($date)->format('D'));

                                $workingHours = WorkingHours::where('day', $day)->get();

                                if (!$workingHours) {
                                    return [];
                                }
                                $slots = [];
                                foreach ($workingHours as $hours) {
                                    $start = Carbon::parse($hours->start);
                                    $end = Carbon::parse($hours->end);
                                    while ($start < $end) {
                                        $hourKey = $start->format('H');
                                        $label = $start->format('h:i A');
                                        if (!isset($usedSlots[$hourKey]) || $usedSlots[$hourKey] < $hours->quantity) {
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
                    $contractId = $this->record->contract_id;
                    $materialCodesWithQuantity = $this->record->getMaterialCodesWithQuantity();
                    // dd($materialCodesWithQuantity);
                    $sapService = new SapService;
                    $sapService->createInbound($bpcode, $contractId, $materialCodesWithQuantity, $date, $time);
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
