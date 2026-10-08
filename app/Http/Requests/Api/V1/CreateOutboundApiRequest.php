<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Material;
use App\Models\WorkingHours;
use App\Services\SapService;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Validator;

class CreateOutboundApiRequest extends BaseApiRequest
{
    protected function prepareForValidation(): void
    {
        $merge = [];
        if (! $this->has('ship_to_party_gln') && $this->has('ship_to_party')) {
            $merge['ship_to_party_gln'] = $this->input('ship_to_party');
        }
        if (! $this->has('delivery_service') && $this->has('shipment_indicator')) {
            $merge['delivery_service'] = filter_var($this->input('shipment_indicator'), FILTER_VALIDATE_BOOLEAN);
        }

        $rawProducts = $this->input('products') ?? $this->input('materials');
        if (is_array($rawProducts)) {
            $normalized = collect($rawProducts)->map(function ($item) {
                if (! isset($item['product_gtin'])) {
                    if (isset($item['gtin'])) {
                        $item['product_gtin'] = $item['gtin'];
                    } elseif (isset($item['material_code'])) {
                        $item['product_gtin'] = $item['material_code'];
                    }
                }
                if (! isset($item['batch_code']) && isset($item['batch'])) {
                    $item['batch_code'] = $item['batch'];
                }
                return $item;
            })->all();
            $merge['products'] = $normalized;
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'delivery_service' => ['required', 'boolean'],
            'ship_to_party_gln' => ['required', 'string'],
            'delivery_date' => ['required'],
            'delivery_time_slot' => ['required'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_gtin' => ['required'],
            'products.*.batch_code' => ['required', 'string'],
            'products.*.quantity' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'delivery_service.required' => 'The delivery_service field is required.',
            'delivery_service.boolean' => 'The delivery_service field must be true or false.',
            'ship_to_party_gln.required' => 'The ship_to_party_gln field is required.',
            'delivery_date.required' => 'The delivery_date field is required.',
            'delivery_time_slot.required' => 'The delivery_time_slot field is required.',
            'products.required' => 'The products field is required.',
            'products.min' => 'The products field must contain at least 1 item.',
            'products.*.product_gtin.required' => 'The product_gtin field is required.',
            'products.*.batch_code.required' => 'The batch_code field is required.',
            'products.*.quantity.required' => 'The quantity field is required.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $deliveryDateRaw = $this->input('delivery_date');
            $timeSlot = (string) $this->input('delivery_time_slot');
            $products = $this->input('products', []);

            // 1. Validate delivery_date format & range
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

            // 2. Validate delivery_time_slot format
            if (! empty($timeSlot)) {
                if (! preg_match('/^(0[0-9]|1[0-9]|2[0-3])$/', $timeSlot)) {
                    $validator->errors()->add(
                        'delivery_time_slot',
                        self::formatError('INVALID_TIME_FORMAT', "The delivery_time_slot field must be a two-digit hour (e.g. '09').")
                    );
                }
            }

            // 3. Validate quantity values
            if (is_array($products)) {
                foreach ($products as $index => $item) {
                    if (isset($item['quantity'])) {
                        if (! is_numeric($item['quantity']) || (int) $item['quantity'] <= 0) {
                            $validator->errors()->add(
                                "products.{$index}.quantity",
                                self::formatError('INVALID_QUANTITY', 'The quantity must be greater than 0.')
                            );
                        }
                    }
                }
            }

            // Stop before upstream checks if basic format errors exist
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

            // 4. Validate Outbound Delivery Time Slot Availability
            try {
                $day = strtolower($parsedDeliveryDate->format('D'));
                $workingHours = WorkingHours::where('day', $day)->get();

                $slotHourInt = (int) $timeSlot;
                $matchedHour = $workingHours->first(function ($wh) use ($slotHourInt) {
                    $startHour = (int) Carbon::parse($wh->start)->format('H');
                    $endHour = (int) Carbon::parse($wh->end)->format('H');
                    return $slotHourInt >= $startHour && $slotHourInt < $endHour;
                });

                if (! $matchedHour) {
                    $validator->errors()->add(
                        'delivery_time_slot',
                        self::formatError('UNAVAILABLE_TIMESLOT', "Time slot not available: Time slot '{$timeSlot}' is fully booked or outside operating hours.")
                    );
                } else {
                    $reservedSlots = $sapService->getOutboundDeliverySlotsReservedCounts($deliveryDateRaw);
                    $usedCount = (int) ($reservedSlots[$timeSlot] ?? 0);

                    if ($usedCount >= $matchedHour->quantity) {
                        $validator->errors()->add(
                            'delivery_time_slot',
                            self::formatError('UNAVAILABLE_TIMESLOT', "Time slot not available: Time slot '{$timeSlot}' is fully booked or outside operating hours.")
                        );
                    }
                }
            } catch (\Throwable $e) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Service not available.',
                ], 502));
            }

            // 5. Validate Stock Availability against SAP
            try {
                $stockData = $sapService->getAllStock($bpCode) ?? [];

                // Group stock by material_code and index batches
                $stockByMatCode = [];
                foreach ($stockData as $stockItem) {
                    $mCode = (string) ($stockItem['material_code'] ?? '');
                    if (! isset($stockByMatCode[$mCode])) {
                        $stockByMatCode[$mCode] = [
                            'material_code' => $mCode,
                            'batches' => [],
                        ];
                    }
                    foreach ($stockItem['batches'] ?? [] as $bItem) {
                        $bCode = (string) ($bItem['batch_code'] ?? '');
                        $bQty = (float) ($bItem['batch_quantity'] ?? 0);
                        if (! isset($stockByMatCode[$mCode]['batches'][$bCode])) {
                            $stockByMatCode[$mCode]['batches'][$bCode] = 0;
                        }
                        $stockByMatCode[$mCode]['batches'][$bCode] += $bQty;
                    }
                }

                // Preload all GTIN mappings
                $requestedGtins = collect($products)->pluck('product_gtin')->filter()->unique()->values();
                $materialsByGtin = Material::whereIn('gtin_code', $requestedGtins)->get()->keyBy('gtin_code');
                $materialsByCode = Material::whereIn('code', $requestedGtins)->get()->keyBy('code');

                // Track accumulated requested quantities per material+batch to prevent overselling
                $accumulatedRequested = [];

                foreach ($products as $index => $item) {
                    $gtinInput = (string) ($item['product_gtin'] ?? '');
                    $bCode = (string) ($item['batch_code'] ?? '');
                    $reqQty = (int) ($item['quantity'] ?? 0);

                    $material = $materialsByGtin->get($gtinInput) ?? $materialsByCode->get($gtinInput);
                    $matCode = $material?->code ?? $gtinInput;

                    // Verify material stock
                    if (! isset($stockByMatCode[$matCode])) {
                        $validator->errors()->add(
                            "products.{$index}.product_gtin",
                            self::formatError(
                                'PRODUCT_GTIN_NOT_IN_STOCK',
                                "Product GTIN not available in stock: Material '{$matCode}' has no stock in warehouse."
                            )
                        );
                        continue;
                    }

                    // Verify batch existence
                    if (! isset($stockByMatCode[$matCode]['batches'][$bCode])) {
                        $validator->errors()->add(
                            "products.{$index}.batch_code",
                            self::formatError(
                                'BATCH_NOT_AVAILABLE',
                                "Batch not available: Batch '{$bCode}' does not exist for this product."
                            )
                        );
                        continue;
                    }

                    // Verify quantity available
                    $availableQty = $stockByMatCode[$matCode]['batches'][$bCode];
                    $aggKey = "{$matCode}|{$bCode}";
                    $currentTotal = ($accumulatedRequested[$aggKey] ?? 0) + $reqQty;
                    $accumulatedRequested[$aggKey] = $currentTotal;

                    if ($currentTotal > $availableQty) {
                        $validator->errors()->add(
                            "products.{$index}.quantity",
                            self::formatError(
                                'QUANTITY_NOT_AVAILABLE',
                                "Quantity not available: Requested quantity ({$reqQty}) exceeds available stock ({$availableQty}) for batch '{$bCode}'."
                            )
                        );
                    }
                }
            } catch (\Throwable $e) {
                // Non-blocking if SAP stock call encounters issue
            }
        });
    }
}
