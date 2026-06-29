<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdvanceTimeRequest;
use App\Models\Order;
use App\Models\Delivery;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\SystemSetting;
use App\Services\SystemTimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TimeSimulationController extends Controller
{
    private const SNAPSHOT_KEY = 'time_simulation_snapshot';
    private const TIME_OFFSET_KEY = 'time_offset_hours';

    public function __construct(
        private SystemTimeService $timeService
    ) {}

    public function show(): JsonResponse
    {
        $offsetHours = $this->timeService->getOffsetHours();
        $realNow = now();
        $simulatedNow = $this->timeService->now();

        // Get current orders status summary
        $ordersSummary = [
            'pending' => Order::where('status', 'sedang_dikemas')->count(),
            'waiting_driver' => Order::where('status', 'menunggu_pengirim')->count(),
            'shipping' => Order::where('status', 'sedang_dikirim')->count(),
            'completed' => Order::where('status', 'pesanan_selesai')->count(),
            'returned' => Order::where('status', 'dikembalikan')->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Status waktu simulasi berhasil diambil',
            'data' => [
                'offset_hours' => $offsetHours,
                'real_time' => $realNow->toIso8601String(),
                'simulated_time' => $simulatedNow->toIso8601String(),
                'is_simulating' => $offsetHours > 0,
                'has_snapshot' => SystemSetting::getValue(self::SNAPSHOT_KEY) !== null,
                'orders_summary' => $ordersSummary,
            ],
        ]);
    }

    public function advance(AdvanceTimeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Take snapshot BEFORE advancing (only if not already simulating)
        if ($this->timeService->getOffsetHours() === 0) {
            $this->takeSnapshot();
        }

        $newOffset = $this->timeService->advanceHours($validated['hours']);

        // Process overdue orders automatically after advance
        $processedCount = $this->processOverdueOrders();

        return response()->json([
            'success' => true,
            'message' => 'Waktu simulasi berhasil dimajukan',
            'data' => [
                'added_hours' => $validated['hours'],
                'new_offset_hours' => $newOffset,
                'simulated_time' => $this->timeService->now()->toIso8601String(),
                'processed_overdue_count' => $processedCount,
            ],
        ]);
    }

    public function reset(): JsonResponse
    {
        $wasSimulating = $this->timeService->getOffsetHours() > 0;

        if ($wasSimulating) {
            // Restore from snapshot
            $result = $this->restoreFromSnapshot();
            $this->timeService->resetOffset();

            return response()->json([
                'success' => true,
                'message' => 'Waktu simulasi berhasil direset - pesanan dikembalikan ke state semula',
                'data' => [
                    'offset_hours' => 0,
                    'real_time' => now()->toIso8601String(),
                    'restored_orders_count' => $result['restored_count'],
                    'refunded_amount' => $result['refunded_amount'],
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Waktu sudah dalam keadaan normal',
            'data' => [
                'offset_hours' => 0,
                'real_time' => now()->toIso8601String(),
                'restored_orders_count' => 0,
            ],
        ]);
    }

    /**
     * Take a snapshot of current order states before simulation
     */
    private function takeSnapshot(): void
    {
        // Get all active orders (not completed or returned)
        $activeOrders = Order::whereIn('status', [
            'sedang_dikemas',
            'menunggu_pengirim',
            'sedang_dikirim'
        ])
        ->with(['items', 'delivery'])
        ->get();

        $snapshot = $activeOrders->map(function ($order) {
            return [
                'order_id' => $order->id,
                'status' => $order->status,
                'delivery_status' => $order->delivery?->status,
                'delivery_due_at' => $order->delivery?->due_at?->toIso8601String(),
                'total' => $order->total,
            ];
        })->toArray();

        SystemSetting::setValue(self::SNAPSHOT_KEY, $snapshot);
    }

    /**
     * Restore orders to their pre-simulation state
     */
    private function restoreFromSnapshot(): array
    {
        $snapshotData = SystemSetting::getValue(self::SNAPSHOT_KEY);

        if (!$snapshotData) {
            return ['restored_count' => 0, 'refunded_amount' => 0];
        }

        $snapshot = is_string($snapshotData) ? json_decode($snapshotData, true) : $snapshotData;
        $restoredCount = 0;
        $refundedAmount = 0;

        foreach ($snapshot as $item) {
            $order = Order::find($item['order_id']);

            if (!$order) {
                continue;
            }

            // Only restore if status is 'dikembalikan' (was processed as overdue)
            if ($order->status === 'dikembalikan') {
                DB::transaction(function () use ($order, $item, &$restoredCount, &$refundedAmount) {
                    // Refund back the amount that was refunded
                    $refundedAmount += $order->total;

                    // Deduct from wallet (reverse the refund)
                    $wallet = Wallet::where('user_id', $order->buyer_id)->first();
                    if ($wallet) {
                        $wallet->decrement('balance', $order->total);

                        // Record the reversal
                        WalletTransaction::create([
                            'wallet_id' => $wallet->id,
                            'order_id' => $order->id,
                            'type' => 'refund_reversal',
                            'amount' => -$order->total,
                            'status' => 'success',
                            'description' => 'Reset simulasi - refund dikembalikan ke sistem',
                        ]);
                    }

                    // Restore order status
                    $order->update(['status' => $item['status']]);

                    // Restore delivery status
                    if ($order->delivery) {
                        $order->delivery->update([
                            'status' => $item['delivery_status'] ?? 'available',
                            'due_at' => $item['delivery_due_at'],
                        ]);
                    }

                    // Deduct stock back (reverse the stock increment)
                    foreach ($order->items as $orderItem) {
                        Product::where('id', $orderItem->product_id)->decrement('stock', $orderItem->quantity);
                    }

                    // Add status history
                    OrderStatusHistory::create([
                        'order_id' => $order->id,
                        'status' => $item['status'],
                        'note' => 'Status dikembalikan oleh reset simulasi waktu',
                    ]);

                    $restoredCount++;
                });
            }
        }

        // Clear the snapshot
        SystemSetting::where('key', self::SNAPSHOT_KEY)->delete();

        return [
            'restored_count' => $restoredCount,
            'refunded_amount' => $refundedAmount,
        ];
    }

    /**
     * Process overdue orders
     */
    private function processOverdueOrders(): int
    {
        $simulatedNow = $this->timeService->now();

        $overdueDeliveries = Delivery::with('order.items')
            ->where('due_at', '<', $simulatedNow)
            ->whereIn('status', ['available', 'taken'])
            ->whereHas('order', function ($query) {
                $query->whereIn('status', ['sedang_dikirim', 'menunggu_pengirim']);
            })
            ->get();

        if ($overdueDeliveries->isEmpty()) {
            return 0;
        }

        $processedCount = 0;

        foreach ($overdueDeliveries as $delivery) {
            $order = $delivery->order;

            if (!in_array($order->status, ['sedang_dikirim', 'menunggu_pengirim'])) {
                continue;
            }

            DB::transaction(function () use ($order, $delivery, &$processedCount) {
                $order->update(['status' => 'dikembalikan']);

                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'status' => 'dikembalikan',
                    'note' => 'Pesanan otomatis dikembalikan karena melebihi batas waktu pengiriman (time simulation)',
                ]);

                $wallet = Wallet::firstOrCreate(
                    ['user_id' => $order->buyer_id],
                    ['balance' => 0]
                );
                $wallet->increment('balance', $order->total);

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'order_id' => $order->id,
                    'type' => 'refund',
                    'amount' => $order->total,
                    'status' => 'success',
                    'description' => 'Refund otomatis - pesanan dikembalikan karena overdue (time simulation)',
                ]);

                foreach ($order->items as $item) {
                    Product::where('id', $item->product_id)->increment('stock', $item->quantity);
                }

                $delivery->update(['status' => 'cancelled']);

                $processedCount++;
            });
        }

        return $processedCount;
    }
}