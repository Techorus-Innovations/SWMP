<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GetSlotsApiRequest;
use App\Models\WorkingHours;
use App\Services\SapService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class SlotApiController extends Controller
{
    public function inboundSlots(GetSlotsApiRequest $request): JsonResponse
    {
        $date = $request->query('date');
        $day = strtolower(Carbon::parse($date)->format('D'));

        try {
            $workingHours = WorkingHours::where('day', $day)->get();
            $sapService = new SapService();
            $usedSlots = $sapService->getDeliverySlotsReservedCounts($date) ?? [];

            $availableSlots = [];
            foreach ($workingHours as $hours) {
                $start = Carbon::parse($hours->start);
                $end = Carbon::parse($hours->end);

                while ($start < $end) {
                    $hourKey = $start->format('H');
                    $reservedCount = (int) ($usedSlots[$hourKey] ?? 0);
                    $capacity = (int) $hours->quantity;

                    if ($reservedCount < $capacity) {
                        $availableSlots[] = $start->format('H:i');
                    }

                    $start->addHour();
                }
            }

            return response()->json([
                'date' => $date,
                'slots' => array_values(array_unique($availableSlots)),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to retrieve inbound slots: ' . $e->getMessage(),
            ], 502);
        }
    }

    public function outboundSlots(GetSlotsApiRequest $request): JsonResponse
    {
        $date = $request->query('date');
        $day = strtolower(Carbon::parse($date)->format('D'));

        try {
            $workingHours = WorkingHours::where('day', $day)->get();
            $sapService = new SapService();
            $usedSlots = $sapService->getOutboundDeliverySlotsReservedCounts($date) ?? [];

            $availableSlots = [];
            foreach ($workingHours as $hours) {
                $start = Carbon::parse($hours->start);
                $end = Carbon::parse($hours->end);

                while ($start < $end) {
                    $hourKey = $start->format('H');
                    $reservedCount = (int) ($usedSlots[$hourKey] ?? 0);
                    $capacity = (int) $hours->quantity;

                    if ($reservedCount < $capacity) {
                        $availableSlots[] = $start->format('H:i');
                    }

                    $start->addHour();
                }
            }

            return response()->json([
                'date' => $date,
                'slots' => array_values(array_unique($availableSlots)),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to retrieve outbound slots: ' . $e->getMessage(),
            ], 502);
        }
    }
}
