<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;

class StoreController extends Controller
{
    public function index()
    {
        $stores = Store::select('id', 'name', 'description', 'image', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $stores->getCollection()->transform(function ($store) {
            return [
                'id' => $store->id,
                'name' => $store->name,
                'description' => $store->description,
                'image' => $store->image,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar toko berhasil diambil',
            'data' => $stores
        ]);
    }

    public function show($id)
    {
        $store = Store::with('products')->find($id);

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Toko tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail toko berhasil diambil',
            'data' => $store
        ]);
    }
}
