<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * List orders untuk buyer yang sedang login
     * GET /api/orders
     */
    public function index(Request $request)
    {
        $orders = Order::with(['store:id,name', 'items', 'address', 'voucher', 'promo'])
            ->where('buyer_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Daftar order berhasil diambil',
            'data' => $orders,
        ]);
    }

    /**
     * Detail order untuk buyer
     * GET /api/orders/{id}
     */
    public function show(Request $request, $id)
    {
        $order = Order::with(['store:id,name', 'items.product', 'address', 'delivery.driver', 'voucher', 'promo'])
            ->where('buyer_id', $request->user()->id)
            ->where('id', $id)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail order berhasil diambil',
            'data' => $order,
        ]);
    }
}
