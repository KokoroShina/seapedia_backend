<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePromoRequest;
use App\Http\Requests\Admin\UpdatePromoRequest;
use App\Models\Promo;
use Illuminate\Http\JsonResponse;

class PromoController extends Controller
{
    public function index(): JsonResponse
    {
        $promos = Promo::orderByDesc('created_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Daftar promo berhasil diambil',
            'data' => $promos,
        ]);
    }

    public function store(StorePromoRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $promo = Promo::create([
            'code' => null,
            'type' => 'percentage',
            'value' => $validated['value'],
            'min_purchase' => $validated['min_purchase'],
            'expired_at' => $validated['expired_at'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Promo berhasil dibuat',
            'data' => $promo,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $promo = Promo::find($id);

        if (!$promo) {
            return response()->json([
                'success' => false,
                'message' => 'Promo tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail promo berhasil diambil',
            'data' => $promo,
        ]);
    }

    public function update(UpdatePromoRequest $request, int $id): JsonResponse
    {
        $promo = Promo::find($id);

        if (!$promo) {
            return response()->json([
                'success' => false,
                'message' => 'Promo tidak ditemukan',
            ], 404);
        }

        $validated = $request->validated();
        $promo->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Promo berhasil diperbarui',
            'data' => $promo,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $promo = Promo::find($id);

        if (!$promo) {
            return response()->json([
                'success' => false,
                'message' => 'Promo tidak ditemukan',
            ], 404);
        }

        $promo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Promo berhasil dihapus',
        ]);
    }
}