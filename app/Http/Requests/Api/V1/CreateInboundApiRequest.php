<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Material;
use App\Models\WorkingHours;
use App\Services\SapService;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Validator;

class CreateInboundApiRequest extends BaseApiRequest
{
    protected function prepareForValidation(): void
    {
        $merge = [];
        if (! $this->has('contract_code') && $this->has('contract_id')) {
            $merge['contract_code'] = $this->input('contract_id');
        }
        if (! $this->has('delivery_time') && $this->has('delivery_time_slot')) {
            $merge['delivery_time'] = $this->input('delivery_time_slot');
        }
        if (! $this->has('products') && $this->has('materials')) {
            $products = collect($this->input('materials', []))->map(function ($item) {
                if (! isset($item['gtin']) && isset($item['gtin_code'])) {
                    $item['gtin'] = $item['gtin_code'];
                }
                return $item;
            })->all();
            $merge['products'] = $products;
        } elseif ($this->has('products')) {
            $products = collect($this->input('products', []))->map(function ($item) {
                if (! isset($item['gtin']) && isset($item['gtin_code'])) {
                    $item['gtin'] = $item['gtin_code'];
                }
                return $item;
            })->all();
            $merge['products'] = $products;
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'contract_code' => ['required'],
            'delivery_date' => ['required'],
            'delivery_time' => ['required'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.gtin' => ['required'],
            'products.*.quantity' => ['required'],
            'products.*.batch' => ['required', 'string', 'max:10'],
            'products.*.manufacture_date' => ['required'],
            'products.*.expiration_date' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'contract_code.required' => 'The contract_code field is required.',
            'delivery_date.required' => 'The delivery_date field is required.',
            'delivery_time.required' => 'The delivery_time field is required.',
            'products.required' => 'The products field is required.',
            'products.min' => 'The products field must contain at least 1 item.',
            'products.*.gtin.required' => 'The gtin field is required.',
            'products.*.quantity.required' => 'The quantity field is required.',
            'products.*.batch.required' => 'The batch field is required.',
            'products.*.batch.max' => 'The batch field must not exceed 10 characters.',
            'products.*.manufacture_date.required' => 'The manufacture_date field is required.',
            'products.*.expiration_date.required' => 'The expiration_date field is required.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $deliveryDateRaw = $this->input('delivery_date');
            $deliveryTimeRaw = (string) $this->input('delivery_time');
            $contractCode = (string) $this->input('contract_code');
            $products = $this->input('products', []);

            // 1. Validate delivery_date format
            $parsedDeliveryDate = null;
            if (! empty($deliveryDateRaw)) {
                try {
                    $d = \DateTime::createFromFormat('Y-m-d', $deliveryDateRaw);
                    if ($d && $d->format('Y-m-d') === $deliveryDateRaw) {
                        $parsedDeliveryDate = Carbon::createFromFormat('Y-m-d', $deliveryDateRaw)->startOfDay();
                    }
                } catch (\Throwable $e) {
                    $parsedDeliveryDate = null;
                }

                if (! $parsedDeliveryDate) {
                    $validator->errors()->add(
                        'delivery_date',
                        self::formatError('INVALID_DATE_FORMAT', 'The delivery_date field must match the format Y-m-d.')
                    );
                } else {
                    $tomorrow = Carbon::now()->addDay()->startOfDay();
                    $maxDate = Carbon::now()->addDays(30)->startOfDay();
                    if ($parsedDeliveryDate->lt($tomorrow) || $parsedDeliveryDate->gt($maxDate)) {
                        $validator->errors()->add(
                            'delivery_date',
                            self::formatError(
                                'INVALID_DELIVERY_DATE_RANGE',
                                "The delivery_date must be between {$tomorrow->format('Y-m-d')} and {$maxDate->format('Y-m-d')}."
                            )
                        );
                    }
                }
            }

            // 2. Validate delivery_time format
            if (! empty($deliveryTimeRaw)) {
                if (! preg_match('/^(0[0-9]|1[0-9]|2[0-3])$/', $deliveryTimeRaw)) {
                    $validator->errors()->add(
                        'delivery_time',
                        self::formatError('INVALID_TIME_FORMAT', "The delivery_time field must be a two-digit hour (e.g. '09').")
                    );
                }
            }

            // 3. Validate products fields
            if (is_array($products)) {
                foreach ($products as $index => $item) {
                    // Quantity validation
                    if (isset($item['quantity'])) {
                        if (! is_numeric($item['quantity']) || (int) $item['quantity'] <= 0) {
                            $validator->errors()->add(
                                "products.{$index}.quantity",
                                self::formatError('INVALID_QUANTITY', 'The quantity must be greater than 0.')
                            );
                        }
                    }

                    // Manufacture date format
                    $parsedMfgDate = null;
                    if (! empty($item['manufacture_date'])) {
                        try {
                            $d = \DateTime::createFromFormat('Y-m-d', $item['manufacture_date']);
                            if ($d && $d->format('Y-m-d') === $item['manufacture_date']) {
                                $parsedMfgDate = Carbon::createFromFormat('Y-m-d', $item['manufacture_date'])->startOfDay();
                            }
                        } catch (\Throwable $e) {
                            $parsedMfgDate = null;
                        }

                        if (! $parsedMfgDate) {
                            $validator->errors()->add(
                                "products.{$index}.manufacture_date",
                                self::formatError('INVALID_DATE_FORMAT', 'The manufacture_date field must match the format Y-m-d.')
                            );
                        } elseif ($parsedDeliveryDate && $parsedMfgDate->gte($parsedDeliveryDate)) {
                            $validator->errors()->add(
                                "products.{$index}.manufacture_date",
                                self::formatError('INVALID_MANUFACTURE_DATE', 'The manufacture_date must be before delivery_date.')
                            );
                        }
                    }

                    // Expiration date format
                    $parsedExpDate = null;
                    if (! empty($item['expiration_date'])) {
                        try {
                            $d = \DateTime::createFromFormat('Y-m-d', $item['expiration_date']);
                            if ($d && $d->format('Y-m-d') === $item['expiration_date']) {
                                $parsedExpDate = Carbon::createFromFormat('Y-m-d', $item['expiration_date'])->startOfDay();
                            }
                        } catch (\Throwable $e) {
                            $parsedExpDate = null;
                        }

                        if (! $parsedExpDate) {
                            $validator->errors()->add(
                                "products.{$index}.expiration_date",
                                self::formatError('INVALID_DATE_FORMAT', 'The expiration_date field must match the format Y-m-d.')
                            );
                        } elseif ($parsedDeliveryDate && $parsedExpDate->lte($parsedDeliveryDate)) {
                            $validator->errors()->add(
                                "products.{$index}.expiration_date",
                                self::formatError('INVALID_EXPIRATION_DATE', 'The expiration_date must be after delivery_date.')
                            );
                        }
                    }
                }
            }

            // Stop before upstream checks if basic schema errors exist
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $partner = $this->attributes->get('authenticated_partner') ?? filament()->auth()->user()?->businessPartner;
            if (! $partner) {
                $validator->errors()->add(
                    'auth',
                    self::formatError('UNAUTHORIZED', 'Could not resolve authenticated Business Partner.')
                );
                return;
            }

            $bpCode = $partner->bp_code;
            $sapService = new SapService();

            // 4. Verify Contract existence in SAP
            $contract = $sapService->findContractById($bpCode, $contractCode);
            if (! $contract) {
                $validator->errors()->add(
                    'contract_code',
                    self::formatError('CONTRACT_NOT_FOUND', "Contract not found: Contract '{$contractCode}' does not exist for this business partner.")
                );
                return;
            }

            if (! empty($contract['contract_end']) && $parsedDeliveryDate && Carbon::parse($contract['contract_end'])->lt($parsedDeliveryDate)) {
                $validator->errors()->add(
                    'contract_code',
                    self::formatError('CONTRACT_NOT_FOUND', "Contract not found: Contract '{$contractCode}' does not exist for this business partner.")
                );
                return;
            }

            $contractAreaCodes = collect($contract['areas'] ?? [])->pluck('area_code')->filter()->all();

            // 5. Verify Material GTIN and storage location eligibility
            $latestSync = Material::max('sync_at');
            foreach ($products as $index => $item) {
                $gtin = trim((string) ($item['gtin'] ?? ''));

                $material = Material::where('gtin_code', $gtin)
                    ->when($latestSync, fn ($q) => $q->where('sync_at', $latestSync))
                    ->first();

                if (! $material) {
                    $material = Material::where('gtin_code', $gtin)->first();
                }

                if (! $material) {
                    $validator->errors()->add(
                        "products.{$index}.gtin",
                        self::formatError('INVALID_GTIN', "Material with GTIN '{$gtin}' does not exist.")
                    );
                    continue;
                }

                // Check first storage location against contract
                $firstArea = $material->areas()->orderBy('material_area.id')->first();
                $locCode = $firstArea->code ?? 'LOC-01';

                if (! $firstArea || ! in_array($firstArea->code, $contractAreaCodes, true)) {
                    $validator->errors()->add(
                        "products.{$index}.gtin",
                        self::formatError(
                            'UNSUPPORTED_GTIN',
                            "Unsupported GTIN (Material storage location not available for your contract): Material '{$gtin}' storage location '{$locCode}' is not permitted for contract '{$contractCode}'."
                        )
                    );
                }
            }

            // 6. Verify Delivery Time Slot Availability
            try {
                $day = strtolower($parsedDeliveryDate->format('D'));
                $workingHours = WorkingHours::where('day', $day)->get();

                $slotHourInt = (int) $deliveryTimeRaw;
                $matchedHour = $workingHours->first(function ($wh) use ($slotHourInt) {
                    $startHour = (int) Carbon::parse($wh->start)->format('H');
                    $endHour = (int) Carbon::parse($wh->end)->format('H');
                    return $slotHourInt >= $startHour && $slotHourInt < $endHour;
                });

                if (! $matchedHour) {
                    $validator->errors()->add(
                        'delivery_time',
                        self::formatError('UNAVAILABLE_TIMESLOT', "Time slot not available: Time slot '{$deliveryTimeRaw}' is fully booked or outside operating hours.")
                    );
                } else {
                    $reservedSlots = $sapService->getDeliverySlotsReservedCounts($deliveryDateRaw);
                    $usedCount = (int) ($reservedSlots[$deliveryTimeRaw] ?? 0);

                    if ($usedCount >= $matchedHour->quantity) {
                        $validator->errors()->add(
                            'delivery_time',
                            self::formatError('UNAVAILABLE_TIMESLOT', "Time slot not available: Time slot '{$deliveryTimeRaw}' is fully booked or outside operating hours.")
                        );
                    }
                }
            } catch (\Throwable $e) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Service not available.',
                ], 502));
            }
        });
    }
}
