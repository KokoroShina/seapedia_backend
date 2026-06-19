<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // Helper ambil toko milik user yang login
    private function getOwnedStore(Request $request)
    {
        return Store::where('user_id', $request->user()->id)->first();
    }

    // List order masuk ke toko
    public function index(Request $request)
    {
        $store = $this->getOwnedStore($request);

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum memiliki toko',
            ], 404);
        }

        $query = Order::with('buyer:id,name,username')
            ->where('store_id', $store->id)
            ->withCount('items')
            ->orderBy('created_at', 'desc');

        // Filter berdasarkan status jika ada
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Daftar order berhasil diambil',
            'data' => $orders,
        ]);
    }

    // Detail order
    public function show(Request $request, $id)
    {
        $store = $this->getOwnedStore($request);

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum memiliki toko',
            ], 404);
        }

        $order = Order::with([
            'buyer:id,name,username,email',
            'items',
            'address',
            'statusHistories' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }
        ])->where('store_id', $store->id)->find($id);

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

    // Proses order (update status: sedang_dikemas -> menunggu_pengirim)
    public function process(Request $request, $id)
    {
        $store = $this->getOwnedStore($request);

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum memiliki toko',
            ], 404);
        }

        $order = Order::where('store_id', $store->id)->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan',
            ], 404);
        }

        // Validasi: hanya bisa diproses jika status saat ini adalah sedang_dikemas
        if ($order->status !== 'sedang_dikemas') {
            return response()->json([
                'success' => false,
                'message' => 'Order ini sudah tidak bisa diproses ulang oleh penjual',
            ], 422);
        }

        // Hitung due_at berdasarkan delivery_method
        $dueAt = match ($order->delivery_method) {
            'instant' => now()->addHours(3),
            'next_day' => now()->addDay(),
            'regular' => now()->addDays(3),
            default => now()->addDays(3),
        };

        // Gunakan DB::transaction untuk atomicity
        DB::transaction(function () use ($order, $dueAt) {
            // Update status order
            $order->status = 'menunggu_pengirim';
            $order->save();

            // Insert record baru ke order_status_histories
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'menunggu_pengirim',
                'note' => 'Pesanan telah dikemas dan menunggu driver',
            ]);

            // Buat record delivery baru
            Delivery::create([
                'order_id' => $order->id,
                'driver_id' => null,
                'status' => 'available',
                'taken_at' => null,
                'completed_at' => null,
                'due_at' => $dueAt,
            ]);
        });

        // Load relasi untuk response
        $order->load([
            'buyer:id,name,username,email',
            'items',
            'address',
            'delivery',
            'statusHistories' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order berhasil diproses dan menunggu pengirim',
            'data' => $order,
        ]);
    }
}
