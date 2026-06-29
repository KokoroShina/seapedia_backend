<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics for seller
     */
    public function index(Request $request): JsonResponse
    {
        $store = Store::where('user_id', $request->user()->id)->first();

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum memiliki toko',
            ], 404);
        }

        // Get order stats
        $orderStats = Order::where('store_id', $store->id)
            ->select([
                DB::raw('COUNT(*) as total_orders'),
                DB::raw("SUM(CASE WHEN status = 'sedang_dikemas' THEN 1 ELSE 0 END) as pending_orders"),
                DB::raw("SUM(CASE WHEN status = 'pesanan_selesai' THEN 1 ELSE 0 END) as completed_orders"),
            ])
            ->first();

        // Get product stats
        $productStats = Product::where('store_id', $store->id)
            ->select([
                DB::raw('COUNT(*) as total_products'),
                DB::raw("SUM(CASE WHEN stock > 0 AND stock <= 5 THEN 1 ELSE 0 END) as low_stock"),
            ])
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard stats berhasil diambil',
            'data' => [
                'total_orders' => (int) ($orderStats->total_orders ?? 0),
                'pending_orders' => (int) ($orderStats->pending_orders ?? 0),
                'completed_orders' => (int) ($orderStats->completed_orders ?? 0),
                'total_products' => (int) ($productStats->total_products ?? 0),
                'low_stock' => (int) ($productStats->low_stock ?? 0),
            ],
        ]);
    }
}
