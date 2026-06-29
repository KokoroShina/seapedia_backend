<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\SystemTimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OrderOverdueController extends Controller
{
    public function __construct(
        private SystemTimeService $timeService
    ) {}

    /**
     * POST /api/admin/orders/check-overdue
     * Jalankan pengecekan dan auto-return untuk order yang overdue.
     */
    public function checkOverdue(): JsonResponse
    {
        $simulatedNow = $this->timeService->now();

        // Cari deliveries yang overdue:
        // - due_at < waktu_simulasi_sekarang
        // - status masih 'taken' ATAU 'available' (belum diambil driver)
        // - Order terkait masih 'sedang_dikirim' ATAU 'menunggu_pengirim'
        $overdueDeliveries = Delivery::with('order.items')
            ->where('due_at', '<', $simulatedNow)
            ->whereIn('status', ['available', 'taken'])
            ->whereHas('order', function ($query) {
                $query->whereIn('status', ['sedang_dikirim', 'menunggu_pengirim']);
            })
            ->get();

        if ($overdueDeliveries->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Tidak ada pesanan yang overdue saat ini',
                'data' => [
                    'processed_count' => 0,
                    'affected_orders' => [],
                ],
            ]);
        }

        $processedOrders = [];

        foreach ($overdueDeliveries as $delivery) {
            $order = $delivery->order;

            // Skip jika order sudah diproses (status sudah berubah)
            if (!in_array($order->status, ['sedang_dikirim', 'menunggu_pengirim'])) {
                continue;
            }

            DB::transaction(function () use ($order, $delivery, &$processedOrders) {
                // 1. Update order status menjadi 'dikembalikan'
                $order->update(['status' => 'dikembalikan']);

                // 2. Insert status history
                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'status' => 'dikembalikan',
                    'note' => 'Pesanan otomatis dikembalikan karena melebihi batas waktu pengiriman',
                ]);

                // 3. Refund penuh ke wallet buyer
                $wallet = Wallet::firstOrCreate(
                    ['user_id' => $order->buyer_id],
                    ['balance' => 0]
                );
                $wallet->increment('balance', $order->total);

                // 4. Insert wallet transaction
                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'order_id' => $order->id,
                    'delivery_id' => null,
                    'type' => 'refund',
                    'amount' => $order->total,
                    'status' => 'success',
                    'description' => 'Refund otomatis - pesanan dikembalikan karena overdue',
                ]);

                // 5. Kembalikan stock produk
                foreach ($order->items as $item) {
                    Product::where('id', $item->product_id)->increment('stock', $item->quantity);
                }

                // 6. Update delivery status menjadi 'cancelled'
                $delivery->update(['status' => 'cancelled']);

                $processedOrders[] = $order->id;
            });
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengecekan overdue selesai',
            'data' => [
                'processed_count' => count($processedOrders),
                'affected_orders' => $processedOrders,
            ],
        ]);
    }
}