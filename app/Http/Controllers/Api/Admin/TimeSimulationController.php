<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdvanceTimeRequest;
use App\Services\SystemTimeService;
use Illuminate\Http\JsonResponse;

class TimeSimulationController extends Controller
{
    public function __construct(
        private SystemTimeService $timeService
    ) {}

    public function show(): JsonResponse
    {
        $offsetHours = $this->timeService->getOffsetHours();
        $realNow = now();
        $simulatedNow = $this->timeService->now();

        return response()->json([
            'success' => true,
            'message' => 'Status waktu simulasi berhasil diambil',
            'data' => [
                'offset_hours' => $offsetHours,
                'real_time' => $realNow->toIso8601String(),
                'simulated_time' => $simulatedNow->toIso8601String(),
                'is_simulating' => $offsetHours > 0,
            ],
        ]);
    }

    public function advance(AdvanceTimeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $newOffset = $this->timeService->advanceHours($validated['hours']);

        return response()->json([
            'success' => true,
            'message' => 'Waktu simulasi berhasil dimajukan',
            'data' => [
                'added_hours' => $validated['hours'],
                'new_offset_hours' => $newOffset,
                'simulated_time' => $this->timeService->now()->toIso8601String(),
            ],
        ]);
    }

    public function reset(): JsonResponse
    {
        $this->timeService->resetOffset();

        return response()->json([
            'success' => true,
            'message' => 'Waktu simulasi berhasil direset',
            'data' => [
                'offset_hours' => 0,
                'real_time' => now()->toIso8601String(),
            ],
        ]);
    }
}