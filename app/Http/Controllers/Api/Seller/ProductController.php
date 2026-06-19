<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // Helper ambil toko milik user yang login
    private function getOwnedStore(Request $request)
    {
        return Store::where('user_id', $request->user()->id)->first();
    }

    // List produk milik toko sendiri
    public function index(Request $request)
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

    // Tambah produk baru
    public function store(Request $request)
    {
        $store = $this->getOwnedStore($request);

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum memiliki toko, buat toko terlebih dahulu',
            ], 404);
        }

        $request->validate([
            'name'        => 'required|string|max:150',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'image'       => 'nullable|image|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product = Product::create([
            'store_id'    => $store->id,
            'name'        => $request->name,
            'description' => $request->description,
            'price'       => $request->price,
            'stock'       => $request->stock,
            'image'       => $imagePath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan',
            'data' => $product,
        ], 201);
    }

    // Detail produk milik sendiri
    public function show(Request $request, $id)
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

    // Update produk milik sendiri
    public function update(Request $request, $id)
    {
        $store = $this->getOwnedStore($request);
        $product = Product::where('id', $id)->where('store_id', $store?->id)->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan',
            ], 404);
        }

        $request->validate([
            'name'        => 'sometimes|required|string|max:150',
            'description' => 'nullable|string',
            'price'       => 'sometimes|required|numeric|min:0',
            'stock'       => 'sometimes|required|integer|min:0',
            'image'       => 'nullable|image|max:2048',
        ]);

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

    // Hapus produk milik sendiri
    public function destroy(Request $request, $id)
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