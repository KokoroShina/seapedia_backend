<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        // Filter by store_id
        if ($request->has('store_id') && $request->store_id !== null && $request->store_id !== '') {
            $query->where('store_id', $request->store_id);
        }

        // Search by name
        if ($request->has('search') && $request->search !== null && $request->search !== '') {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(15);

        // Transform collection to return only requested fields
        $products->getCollection()->transform(function ($product) {
            return [
                'id' => $product->id,
                'store_id' => $product->store_id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'stock' => $product->stock,
                'image' => $product->image ? '/storage/' . $product->image : null,
                'image_url' => $product->imageUrl,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar produk berhasil diambil',
            'data' => $products
        ]);
    }

    public function show($id)
    {
        $product = Product::with('store:id,name')->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail produk berhasil diambil',
            'data' => $product
        ]);
    }
}
