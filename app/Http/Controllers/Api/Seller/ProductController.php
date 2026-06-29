<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\StoreProductRequest;
use App\Http\Requests\Seller\UpdateProductRequest;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    private function getOwnedStore(Request $request)
    {
        return Store::where('user_id', $request->user()->id)->first();
    }

    public function index(Request $request): JsonResponse
    {
        $store = $this->getOwnedStore($request);

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum memiliki toko',
            ], 404);
        }

        $products = Product::where('store_id', $store->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Daftar produk berhasil diambil',
            'data' => $products,
        ]);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $store = $this->getOwnedStore($request);

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum memiliki toko, buat toko terlebih dahulu',
            ], 404);
        }

        $validated = $request->validated();

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product = Product::create([
            'store_id'    => $store->id,
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price'       => $validated['price'],
            'stock'       => $validated['stock'],
            'image'       => $imagePath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan',
            'data' => $product,
        ], 201);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $store = $this->getOwnedStore($request);
        $product = Product::where('id', $id)->where('store_id', $store?->id)->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail produk berhasil diambil',
            'data' => $product,
        ]);
    }

    public function update(UpdateProductRequest $request, $id): JsonResponse
    {
        $store = $this->getOwnedStore($request);
        $product = Product::where('id', $id)->where('store_id', $store?->id)->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan',
            ], 404);
        }

        $validated = $request->validated();

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $product->image = $request->file('image')->store('products', 'public');
        }

        $product->fill($request->only(['name', 'description', 'price', 'stock']));
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diperbarui',
            'data' => $product,
        ]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $store = $this->getOwnedStore($request);
        $product = Product::where('id', $id)->where('store_id', $store?->id)->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan',
            ], 404);
        }

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dihapus',
        ]);
    }
}