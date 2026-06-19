<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    // Lihat isi cart + detail produk + subtotal
    public function index(Request $request)
    {
        $cart = Cart::where('user_id', $request->user()->id)
            ->with('items.product', 'store:id,name')
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Cart kosong',
                'data' => null,
            ]);
        }

        $items = $cart->items->map(function ($item) {
            return [
                'id'         => $item->id,
                'product_id' => $item->product_id,
                'name'       => $item->product->name,
                'price'      => $item->product->price,
                'quantity'   => $item->quantity,
                'subtotal'   => $item->product->price * $item->quantity,
            ];
        });

        $total = $items->sum('subtotal');

        return response()->json([
            'success' => true,
            'message' => 'Cart berhasil diambil',
            'data' => [
                'store' => $cart->store,
                'items' => $items,
                'total' => $total,
            ],
        ]);
    }

    // Tambah produk ke cart
    public function addItem(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        $product = Product::find($request->product_id);
        $cart = Cart::where('user_id', $request->user()->id)->first();

        // Cart belum ada -> buat baru dengan store_id dari produk ini
        if (!$cart) {
            $cart = Cart::create([
                'user_id'  => $request->user()->id,
                'store_id' => $product->store_id,
            ]);
        }

        // Cart sudah ada isi dari toko lain -> tolak
        if ($cart->store_id !== null && $cart->store_id !== $product->store_id && $cart->items()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart Anda berisi produk dari toko lain. Selesaikan atau kosongkan cart terlebih dahulu.',
            ], 422);
        }

        // Kalau cart kosong tapi store_id beda (sisa dari cart sebelumnya), update store_id
        if (!$cart->items()->exists()) {
            $cart->update(['store_id' => $product->store_id]);
        }

        // Cek produk sudah ada di cart -> merge quantity
        $existingItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existingItem) {
            $existingItem->increment('quantity', $request->quantity);
        } else {
            CartItem::create([
                'cart_id'    => $cart->id,
                'product_id' => $product->id,
                'quantity'   => $request->quantity,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan ke cart',
        ], 201);
    }

    // Update quantity item di cart
    public function updateItem(Request $request, $itemId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = Cart::where('user_id', $request->user()->id)->first();
        $item = CartItem::where('id', $itemId)->where('cart_id', $cart?->id)->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item tidak ditemukan di cart Anda',
            ], 404);
        }

        $item->update(['quantity' => $request->quantity]);

        return response()->json([
            'success' => true,
            'message' => 'Quantity berhasil diperbarui',
            'data' => $item,
        ]);
    }

    // Hapus item dari cart
    public function removeItem(Request $request, $itemId)
    {
        $cart = Cart::where('user_id', $request->user()->id)->first();
        $item = CartItem::where('id', $itemId)->where('cart_id', $cart?->id)->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item tidak ditemukan di cart Anda',
            ], 404);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item berhasil dihapus dari cart',
        ]);
    }
}