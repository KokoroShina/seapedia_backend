<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\CheckoutRequest;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\Promo;
use App\Models\Voucher;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/* @noinspection PhpParamsInspection */

class CheckoutController extends Controller
{
    const DELIVERY_FEES = [
        'instant'  => 20000,
        'next_day' => 12000,
        'regular'  => 8000,
    ];

    private function getOrCreateWallet(int $userId): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $userId],
            ['balance' => 0]
        );
    }

    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = $request->user();

        $address = Address::where('id', $validated['address_id'])
            ->where('user_id', $user->id)
            ->first();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Alamat tidak ditemukan',
            ], 404);
        }

        $cart = Cart::where('user_id', $user->id)
            ->with('items.product')
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart kosong',
            ], 400);
        }

        $subtotal = $cart->items->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        foreach ($cart->items as $item) {
            $product = $item->product;
            if ($product->stock < $item->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "Stok {$product->name} tidak mencukupi. Sisa stok: {$product->stock}",
                ], 422);
            }
        }

        $voucherId = null;
        $promoId = null;
        $voucherValue = 0;
        $promoValue = 0;
        $discountAmount = 0;
        $voucher = null;

        if ($request->voucher_code) {
            $voucher = Voucher::where('code', $request->voucher_code)->first();

            if (!$voucher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode voucher tidak valid',
                ], 422);
            }

            if ($voucher->isExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode voucher sudah kadaluarsa',
                ], 422);
            }

            if ($voucher->isMaxUsageReached()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode voucher sudah mencapai batas penggunaan',
                ], 422);
            }

            $voucherId = $voucher->id;
            $voucherValue = (float) $voucher->value;
        }

        $validPromo = Promo::valid($subtotal)
            ->orderByHighestValue()
            ->first();

        if ($validPromo) {
            $promoId = $validPromo->id;
            $promoValue = (float) $validPromo->value;
        }

        $totalPercentage = $voucherValue + $promoValue;

        if ($totalPercentage > 0) {
            $discountAmount = round($subtotal * $totalPercentage / 100);
        }

        $deliveryFee = self::DELIVERY_FEES[$validated['delivery_method']];
        $ppn = round($subtotal * 0.12);
        $total = $subtotal + $deliveryFee + $ppn - $discountAmount;

        $wallet = $this->getOrCreateWallet($user->id);
        if ($wallet->balance < $total) {
            return response()->json([
                'success' => false,
                'message' => 'Saldo wallet tidak mencukupi',
            ], 422);
        }

        $cartId = $cart->id;
        $cartItems = $cart->items->toArray();
        $storeId = $cart->store_id;
        $userId = $user->id;
        $deliveryMethod = $validated['delivery_method'];

        $order = DB::transaction(function () use ($cartId, $cartItems, $storeId, $userId, $address, $subtotal, $deliveryFee, $ppn, $total, $discountAmount, $voucherId, $promoId, $voucher, $deliveryMethod) {
            $order = Order::create([
                'buyer_id'        => $userId,
                'store_id'        => $storeId,
                'address_id'      => $address->id,
                'voucher_id'      => $voucherId,
                'promo_id'        => $promoId,
                'delivery_method' => $deliveryMethod,
                'subtotal'        => $subtotal,
                'discount_amount' => $discountAmount,
                'delivery_fee'    => $deliveryFee,
                'ppn'             => $ppn,
                'total'           => $total,
                'status'          => 'sedang_dikemas',
            ]);

            if ($voucherId && isset($voucher)) {
                Voucher::where('id', $voucherId)->update([
                    'used_count' => DB::raw('used_count + 1'),
                ]);
            }

            foreach ($cartItems as $cartItem) {
                $product = Product::find($cartItem['product_id']);
                $itemSubtotal = $product->price * $cartItem['quantity'];

                OrderItem::create([
                    'order_id'      => $order->id,
                    'product_id'    => $product->id,
                    'product_name'  => $product->name,
                    'product_price' => $product->price,
                    'quantity'      => $cartItem['quantity'],
                    'subtotal'      => $itemSubtotal,
                ]);

                Product::where('id', $product->id)->update([
                    'stock' => DB::raw("stock - {$cartItem['quantity']}"),
                ]);
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status'   => 'sedang_dikemas',
                'note'     => 'Pesanan berhasil dibuat',
            ]);

            $wallet = Wallet::where('user_id', $userId)->first();
            Wallet::where('id', $wallet->id)->update([
                'balance' => DB::raw("balance - {$total}"),
            ]);

            WalletTransaction::create([
                'wallet_id'   => $wallet->id,
                'type'        => 'checkout',
                'amount'      => -$total,
                'status'      => 'success',
                'description' => "Pembayaran order #{$order->id}",
            ]);

            CartItem::where('cart_id', $cartId)->delete();
            Cart::where('id', $cartId)->delete();

            return $order;
        });

        $order->load('items', 'statusHistories', 'address', 'store', 'voucher', 'promo');

        $orderData = $order->toArray();
        $orderData['discount_details'] = [
            'discount_amount' => $discountAmount,
            'voucher_id' => $voucherId,
            'promo_id' => $promoId,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Checkout berhasil',
            'data' => $orderData,
        ], 201);
    }
}