<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/* @noinspection PhpParamsInspection */

class CheckoutController extends Controller
{
    // Constant untuk delivery fee
    const DELIVERY_FEES = [
        'instant'  => 20000,
        'next_day' => 12000,
        'regular'  => 8000,
    ];

    // Helper: ambil atau buat wallet user
    private function getOrCreateWallet(int $userId): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $userId],
            ['balance' => 0]
        );
    }

    // Checkout
    public function checkout(Request $request)
    {
        // Validasi input
        $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'delivery_method' => 'required|in:instant,next_day,regular',
            'voucher_code' => 'nullable|string|max:50',
        ]);

        $user = $request->user();

        // Validasi address milik user
        $address = Address::where('id', $request->address_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Alamat tidak ditemukan',
            ], 404);
        }

        // Ambil cart user
        $cart = Cart::where('user_id', $user->id)
            ->with('items.product')
            ->first();

        // Validasi cart tidak kosong
        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart kosong',
            ], 400);
        }

        // Hitung subtotal
        $subtotal = $cart->items->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        // Validasi stock ALL-OR-NOTHING
        foreach ($cart->items as $item) {
            $product = $item->product;
            if ($product->stock < $item->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "Stok {$product->name} tidak mencukupi. Sisa stok: {$product->stock}",
                ], 422);
            }
        }

        // ==========================================
        // LOGIC VOUCHER & PROMO
        // ==========================================

        $voucherId = null;
        $promoId = null;
        $voucherValue = 0;
        $promoValue = 0;
        $discountAmount = 0;

        // Validasi voucher jika buyer mengirim voucher_code
        if ($request->voucher_code) {
            $voucher = Voucher::where('code', $request->voucher_code)->first();

            // Voucher tidak ditemukan
            if (!$voucher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode voucher tidak valid',
                ], 422);
            }

            // Voucher expired
            if ($voucher->isExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode voucher sudah kadaluarsa',
                ], 422);
            }

            // Voucher mencapai batas penggunaan
            if ($voucher->isMaxUsageReached()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode voucher sudah mencapai batas penggunaan',
                ], 422);
            }

            // Voucher valid
            $voucherId = $voucher->id;
            $voucherValue = (float) $voucher->value;
        }

        // Cari promo otomatis berdasarkan subtotal
        // Pilih promo dengan value (persentase) TERBESAR jika ada lebih dari satu
        $validPromo = Promo::valid($subtotal)
            ->orderByHighestValue()
            ->first();

        if ($validPromo) {
            $promoId = $validPromo->id;
            $promoValue = (float) $validPromo->value;
        }

        // Hitung total persentase diskon (stacking voucher + promo)
        $totalPercentage = $voucherValue + $promoValue;

        // Hitung discount_amount = round(subtotal * total_percentage / 100)
        if ($totalPercentage > 0) {
            $discountAmount = round($subtotal * $totalPercentage / 100);
        }

        // ==========================================
        // HITUNG TOTAL
        // ==========================================

        // Hitung delivery fee
        $deliveryFee = self::DELIVERY_FEES[$request->delivery_method];

        // Hitung PPN (12% dari subtotal SEBELUM diskon)
        $ppn = round($subtotal * 0.12);

        // Hitung total: subtotal + delivery_fee + ppn - discount_amount
        $total = $subtotal + $deliveryFee + $ppn - $discountAmount;

        // Validasi saldo wallet
        $wallet = $this->getOrCreateWallet($user->id);
        if ($wallet->balance < $total) {
            return response()->json([
                'success' => false,
                'message' => 'Saldo wallet tidak mencukupi',
            ], 422);
        }

        // Simpan data untuk digunakan di transaction
        $cartId = $cart->id;
        $cartItems = $cart->items->toArray();
        $storeId = $cart->store_id;
        $userId = $user->id;

        // Proses checkout dalam satu transaksi
        $order = DB::transaction(function () use ($cartId, $cartItems, $storeId, $userId, $address, $request, $subtotal, $deliveryFee, $ppn, $total, $discountAmount, $voucherId, $promoId, $voucher) {
            // Buat order dengan voucher_id dan promo_id
            $order = Order::create([
                'buyer_id'        => $userId,
                'store_id'        => $storeId,
                'address_id'      => $address->id,
                'voucher_id'      => $voucherId,
                'promo_id'        => $promoId,
                'delivery_method' => $request->delivery_method,
                'subtotal'        => $subtotal,
                'discount_amount' => $discountAmount,
                'delivery_fee'    => $deliveryFee,
                'ppn'             => $ppn,
                'total'           => $total,
                'status'          => 'sedang_dikemas',
            ]);

            // Increment used_count voucher jika voucher dipakai
            if ($voucherId && isset($voucher)) {
                Voucher::where('id', $voucherId)->update([
                    'used_count' => DB::raw('used_count + 1'),
                ]);
            }

            // Buat order items dengan snapshot data produk
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

                // Kurangi stock produk menggunakan DB::table
                Product::where('id', $product->id)->update([
                    'stock' => DB::raw("stock - {$cartItem['quantity']}"),
                ]);
            }

            // Buat status history
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status'   => 'sedang_dikemas',
                'note'     => 'Pesanan berhasil dibuat',
            ]);

            // Kurangi saldo wallet menggunakan DB::table
            $wallet = Wallet::where('user_id', $userId)->first();
            Wallet::where('id', $wallet->id)->update([
                'balance' => DB::raw("balance - {$total}"),
            ]);

            // Buat wallet transaction
            WalletTransaction::create([
                'wallet_id'   => $wallet->id,
                'type'        => 'checkout',
                'amount'      => -$total,
                'status'      => 'success',
                'description' => "Pembayaran order #{$order->id}",
            ]);

            // Kosongkan cart: hapus cart_items lalu cart
            CartItem::where('cart_id', $cartId)->delete();
            Cart::where('id', $cartId)->delete();

            return $order;
        });

        // Load relasi dan return response
        $order->load('items', 'statusHistories', 'address', 'store', 'voucher', 'promo');

        // Tambahkan detail discount ke response
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
