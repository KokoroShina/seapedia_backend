<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics for driver
     */
    public function index(Request $request): JsonResponse
    {
        $driverId = $request->user()->id;

        // Get delivery stats
        $totalDeliveries = Delivery::where('driver_id', $driverId)->count();
        $completedDeliveries = Delivery::where('driver_id', $driverId)
            ->where('status', 'completed')
            ->count();

        // Get total earnings from wallet transactions
        $totalEarnings = WalletTransaction::where('type', 'earning')
            ->whereHas('wallet', function ($query) use ($driverId) {
                $query->where('user_id', $driverId);
            })
            ->sum('amount');

        return response()->json([
            'success' => true,
            'message' => 'Dashboard stats berhasil diambil',
            'data' => [
                'total_deliveries' => $totalDeliveries,
                'completed_deliveries' => $completedDeliveries,
                'total_earnings' => (float) $totalEarnings,
            ],
        ]);
    }
}
