<?php

namespace App\Http\Requests\Api\V1;

use Carbon\Carbon;
use Illuminate\Validation\Validator;

class GetSlotsApiRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'date' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'The date field is required.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $date = $this->query('date');
            if (empty($date)) {
                return;
            }

            $parsedDate = null;
            try {
                $d = \DateTime::createFromFormat('Y-m-d', $date);
                if ($d && $d->format('Y-m-d') === $date) {
                    $parsedDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
                }
            } catch (\Throwable $e) {
                $parsedDate = null;
            }

            if (! $parsedDate) {
                $validator->errors()->add(
                    'date',
                    self::formatError('INVALID_DATE_FORMAT', 'The date field must match the format Y-m-d.')
                );
                return;
            }

            $tomorrow = Carbon::now()->addDay()->startOfDay();
            $maxDate = Carbon::now()->addDays(30)->startOfDay();

            if ($parsedDate->lt($tomorrow) || $parsedDate->gt($maxDate)) {
                $validator->errors()->add(
                    'date',
                    self::formatError(
                        'INVALID_DATE_RANGE',
                        "The date must be between {$tomorrow->format('Y-m-d')} and {$maxDate->format('Y-m-d')}."
                    )
                );
            }
        });
    }
}
