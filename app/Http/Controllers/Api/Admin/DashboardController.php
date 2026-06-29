<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Store;
use App\Models\Product;
use App\Models\Order;
use App\Models\Delivery;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $totalUsers = User::count();

        // Count by role using role relationship
        $buyers = User::whereHas('roles', function($q) {
            $q->where('name', 'buyer');
        })->count();
        $sellers = User::whereHas('roles', function($q) {
            $q->where('name', 'seller');
        })->count();
        $drivers = User::whereHas('roles', function($q) {
            $q->where('name', 'driver');
        })->count();

        $totalStores = Store::count();
        $totalProducts = Product::count();
        $lowStockProducts = Product::whereBetween('stock', [1, 5])->count();
        $outOfStockProducts = Product::where('stock', 0)->count();

        $totalOrders = Order::count();
        $pendingOrders = Order::where('status', 'sedang_dikemas')->count();
        $shippingOrders = Order::where('status', 'sedang_dikirim')->count();
        $completedOrders = Order::where('status', 'pesanan_selesai')->count();

        $availableJobs = Delivery::where('status', 'available')->count();
        $activeDeliveries = Delivery::where('status', 'taken')->count();

        $totalWalletBalance = Wallet::sum('balance');

        $recentOrders = Order::with(['buyer', 'store'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'buyer_name' => $order->buyer?->username ?? 'Unknown',
                    'store_name' => $order->store?->name ?? 'Unknown',
                    'total' => $order->total,
                    'status' => $order->status,
                    'created_at' => $order->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Dashboard stats retrieved',
            'data' => [
                'total_users' => $totalUsers,
                'buyers' => $buyers,
                'sellers' => $sellers,
                'drivers' => $drivers,
                'total_stores' => $totalStores,
                'total_products' => $totalProducts,
                'low_stock_products' => $lowStockProducts,
                'out_of_stock_products' => $outOfStockProducts,
                'total_orders' => $totalOrders,
                'pending_orders' => $pendingOrders,
                'shipping_orders' => $shippingOrders,
                'completed_orders' => $completedOrders,
                'available_jobs' => $availableJobs,
                'active_deliveries' => $activeDeliveries,
                'total_wallet_balance' => (float) $totalWalletBalance,
                'recent_orders' => $recentOrders,
            ],
        ]);
    }

    public function users(): JsonResponse
    {
        $users = User::orderBy('created_at', 'desc')->paginate(20);
        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    public function stores(): JsonResponse
    {
        $stores = Store::with('user:id,name')
            ->withCount('products', 'orders')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        return response()->json([
            'success' => true,
            'data' => $stores,
        ]);
    }

    public function products(): JsonResponse
    {
        $products = Product::with('store:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function orders(): JsonResponse
    {
        $orders = Order::with('buyer:id,name', 'store:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    public function deliveries(): JsonResponse
    {
        $deliveries = Delivery::with('order:id,total,status', 'driver:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        return response()->json([
            'success' => true,
            'data' => $deliveries,
        ]);
    }
}
