<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoreController extends Controller
{
    // Lihat toko milik seller yang login
    public function show(Request $request)
    {
        $store = Store::where('user_id', $request->user()->id)->first();

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum memiliki toko',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail toko berhasil diambil',
            'data' => $store,
        ]);
    }

    // Bikin toko baru (cuma sekali per seller)
    public function store(Request $request)
    {
        $existing = Store::where('user_id', $request->user()->id)->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah memiliki toko',
            ], 422);
        }

        $request->validate([
            'name'        => 'required|string|max:150',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('stores', 'public');
        }

        $store = Store::create([
            'user_id'     => $request->user()->id,
            'name'        => $request->name,
            'description' => $request->description,
            'image'       => $imagePath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Toko berhasil dibuat',
            'data' => $store,
        ], 201);
    }

    // Update toko milik sendiri
    public function update(Request $request)
    {
        $store = Store::where('user_id', $request->user()->id)->first();

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum memiliki toko',
            ], 404);
        }

        $request->validate([
            'name'        => 'sometimes|required|string|max:150',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($store->image) {
                Storage::disk('public')->delete($store->image);
            }
            $store->image = $request->file('image')->store('stores', 'public');
        }

        $store->fill($request->only(['name', 'description']));
        $store->save();

        return response()->json([
            'success' => true,
            'message' => 'Toko berhasil diperbarui',
            'data' => $store,
        ]);
    }
}