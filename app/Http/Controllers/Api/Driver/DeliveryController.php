<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryController extends Controller
{
    /**
     * List semua delivery yang tersedia (status = 'available')
     */
    public function availableJobs()
    {
        $deliveries = Delivery::with([
            'order:id,buyer_id,store_id,address_id,delivery_method,total,status',
            'order.address:id,address,recipient_name,phone'
        ])
            ->where('status', 'available')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Daftar job berhasil diambil',
            'data' => $deliveries,
        ]);
    }

    /**
     * Driver mengambil job delivery
     */
    public function take(Request $request, $deliveryId)
    {
        $driverId = $request->user()->id;

        // Cek apakah driver sudah memiliki delivery aktif
        $activeDelivery = Delivery::where('driver_id', $driverId)
            ->where('status', 'taken')
            ->first();

        if ($activeDelivery) {
            return response()->json([
                'success' => false,
                'message' => 'Anda masih memiliki pengiriman aktif yang belum selesai',
            ], 422);
        }

        // Cek delivery yang akan diambil
        $delivery = Delivery::find($deliveryId);

        if (!$delivery) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery tidak ditemukan',
            ], 404);
        }

        // Cek apakah delivery masih available
        if ($delivery->status !== 'available') {
            return response()->json([
                'success' => false,
                'message' => 'Job ini sudah diambil driver lain',
            ], 422);
        }

        // Ambil order terkait
        $order = Order::find($delivery->order_id);

        // Update delivery dan order dalam transaction
        DB::transaction(function () use ($delivery, $order, $driverId) {
            // Update delivery
            $delivery->status = 'taken';
            $delivery->driver_id = $driverId;
            $delivery->taken_at = now();
            $delivery->save();

            // Update order status
            $order->status = 'sedang_dikirim';
            $order->save();

            // Insert status history
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'sedang_dikirim',
                'note' => 'Pesanan sedang dalam pengiriman',
            ]);
        });

        // Load relasi untuk response
        $delivery->load([
            'order.address',
            'driver:id,name,username'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Job berhasil diambil',
            'data' => $delivery,
        ]);
    }

    /**
     * Driver menyelesaikan job delivery
     */
    public function complete(Request $request, $deliveryId)
    {
        $driverId = $request->user()->id;

        $delivery = Delivery::with('order')->find($deliveryId);

        if (!$delivery) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery tidak ditemukan',
            ], 404);
        }

        // Cek apakah delivery milik driver ini
        if ($delivery->driver_id !== $driverId) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk pengiriman ini',
            ], 403);
        }

        // Cek apakah delivery berstatus taken
        if ($delivery->status !== 'taken') {
            return response()->json([
                'success' => false,
                'message' => 'Pengiriman ini belum dalam status diambil',
            ], 422);
        }

        $order = $delivery->order;

        // Hitung earning driver (80% dari delivery_fee)
        $earning = round($order->delivery_fee * 0.8);

        // Update semua dalam transaction
        DB::transaction(function () use ($delivery, $order, $driverId, $earning) {
            // Update delivery
            $delivery->status = 'completed';
            $delivery->completed_at = now();
            $delivery->save();

            // Update order status
            $order->status = 'pesanan_selesai';
            $order->save();

            // Insert status history
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'pesanan_selesai',
                'note' => 'Pesanan telah selesai diterima',
            ]);

            // Buat atau get wallet driver
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $driverId],
                ['balance' => 0]
            );

            // Tambah saldo wallet
            $wallet->balance += $earning;
            $wallet->save();

            // Insert wallet transaction
            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'order_id' => $order->id,
                'delivery_id' => $delivery->id,
                'type' => 'earning',
                'amount' => $earning,
                'status' => 'success',
                'payment_reference' => null,
                'payment_method' => null,
                'description' => "Earning dari pengiriman order #{$order->id}",
            ]);
        });

        // Load relasi untuk response
        $delivery->load([
            'order.address',
            'driver:id,name,username'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengiriman berhasil diselesaikan. Earning: Rp ' . number_format($earning, 0, ',', '.'),
            'data' => $delivery,
        ]);
    }

    /**
     * Riwayat delivery driver
     */
    public function history(Request $request)
    {
        $driverId = $request->user()->id;

        $deliveries = Delivery::with([
            'order:id,total,delivery_method,status',
            'order.address:id,recipient_name,address'
        ])
            ->where('driver_id', $driverId)
            ->orderBy('taken_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Riwayat delivery berhasil diambil',
            'data' => $deliveries,
        ]);
    }

    /**
     * Delivery aktif driver saat ini
     */
    public function active(Request $request)
    {
        $driverId = $request->user()->id;

        $delivery = Delivery::with([
            'order.address',
            'order.buyer:id,name,username,phone'
        ])
            ->where('driver_id', $driverId)
            ->where('status', 'taken')
            ->first();

        return response()->json([
            'success' => true,
            'message' => $delivery ? 'Delivery aktif ditemukan' : 'Tidak ada delivery aktif',
            'data' => $delivery,
        ]);
    }
}
