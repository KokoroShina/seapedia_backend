<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request): JsonResponse
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

    public function addItem(AddCartItemRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $product = Product::find($validated['product_id']);
        $cart = Cart::where('user_id', $request->user()->id)->first();

        if (!$cart) {
            $cart = Cart::create([
                'user_id'  => $request->user()->id,
                'store_id' => $product->store_id,
            ]);
        }

        if ($cart->store_id !== null && $cart->store_id !== $product->store_id && $cart->items()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart Anda berisi produk dari toko lain. Selesaikan atau kosongkan cart terlebih dahulu.',
            ], 422);
        }

        if (!$cart->items()->exists()) {
            $cart->update(['store_id' => $product->store_id]);
        }

        $existingItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existingItem) {
            $existingItem->increment('quantity', $validated['quantity']);
        } else {
            CartItem::create([
                'cart_id'    => $cart->id,
                'product_id' => $product->id,
                'quantity'   => $validated['quantity'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan ke cart',
        ], 201);
    }

    public function updateItem(UpdateCartItemRequest $request, $itemId): JsonResponse
    {
        $validated = $request->validated();

        $cart = Cart::where('user_id', $request->user()->id)->first();
        $item = CartItem::where('id', $itemId)->where('cart_id', $cart?->id)->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item tidak ditemukan di cart Anda',
            ], 404);
        }

        $item->update(['quantity' => $validated['quantity']]);

        return response()->json([
            'success' => true,
            'message' => 'Quantity berhasil diperbarui',
            'data' => $item,
        ]);
    }

    public function removeItem(Request $request, $itemId): JsonResponse
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