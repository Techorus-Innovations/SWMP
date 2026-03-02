<?php

namespace App\Filament\Admin\Resources\WorkingHours\Schemas;

use Carbon\Carbon;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

class WorkingHoursForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('day')
                    ->required()
                    ->options(['Sun' => 'Sunday', 'Mon' => 'Monday', 'Tue' => 'Tuesday', 'Wed' => 'Wednesday', 'Thu' => 'Thursday', 'Fri' => 'Friday', 'Sat' => 'Saturday']),
                TimePicker::make('start')
                    ->seconds(false)
                    ->native(false)
                    ->minutesStep(60)
                    ->required()
                    ->rules([
                        fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                            $start = date('H:i:s', strtotime($value));

                            $flag = DB::table('working_hours')
                                ->where('day', $get('day'))
                                ->where('start', '<', $start)
                                ->where('end', '>', $start)
                                ->count();

                            if ($flag >= 1) {
                                $fail('The start time has overlap.');
                            }
                        },
                    ]),
                TimePicker::make('end')
                    ->seconds(false)
                    ->native(false)
                    ->minutesStep(60)
                    ->required()
                    ->rules([
                        fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                            $start = date('H:i:s', strtotime($get('start')));
                            $end = date('H:i:s', strtotime($value));

                            if ($end <= $start) {
                                $fail('End time must be greater than start time.');
                            } else {
                                $flag = DB::table('working_hours')
                                    ->where('day', $get('day'))
                                    ->where('start', '>=', $start)
                                    ->where('start', '<=', $end)
                                    ->count();    
                                if ($flag >= 1) {
                                    $fail('The end time has overlap.');
                                }
                            }
                        },
                    ]),
                TextInput::make('quantity')
                    ->label(__('Max Requests Per Slot Per Hour'))
                    ->numeric()
                    ->required()
                    ->minValue(1),
            ]);
    }
}
