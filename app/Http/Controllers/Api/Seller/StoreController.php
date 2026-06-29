<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\StoreStoreRequest;
use App\Http\Requests\Seller\UpdateStoreRequest;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoreController extends Controller
{
    public function show(Request $request): JsonResponse
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

    public function store(StoreStoreRequest $request): JsonResponse
    {
        $existing = Store::where('user_id', $request->user()->id)->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah memiliki toko',
            ], 422);
        }

        $validated = $request->validated();

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('stores', 'public');
        }

        $store = Store::create([
            'user_id'     => $request->user()->id,
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'image'       => $imagePath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Toko berhasil dibuat',
            'data' => $store,
        ], 201);
    }

    public function update(UpdateStoreRequest $request): JsonResponse
    {
        $store = Store::where('user_id', $request->user()->id)->first();

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum memiliki toko',
            ], 404);
        }

        $validated = $request->validated();

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