<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promo;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    /**
     * Display a listing of all promos (paginated, newest first)
     * GET /api/admin/promos
     */
    public function index()
    {
        $promos = Promo::orderByDesc('created_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Daftar promo berhasil diambil',
            'data' => $promos,
        ]);
    }

    /**
     * Store a newly created promo
     * POST /api/admin/promos
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'value' => 'required|numeric|min:1|max:100',
            'min_purchase' => 'required|numeric|min:0',
            'expired_at' => 'required|date|after:now',
        ]);

        $promo = Promo::create([
            'code' => null, // Promo tidak pakai kode, selalu null
            'type' => 'percentage', // Selalu percentage untuk saat ini
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

    /**
     * Display the specified promo
     * GET /api/admin/promos/{id}
     */
    public function show(int $id)
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

    /**
     * Update the specified promo
     * PUT /api/admin/promos/{id}
     */
    public function update(Request $request, int $id)
    {
        $promo = Promo::find($id);

        if (!$promo) {
            return response()->json([
                'success' => false,
                'message' => 'Promo tidak ditemukan',
            ], 404);
        }

        $validated = $request->validate([
            'value' => 'sometimes|required|numeric|min:1|max:100',
            'min_purchase' => 'sometimes|required|numeric|min:0',
            'expired_at' => 'sometimes|required|date|after:now',
        ]);

        $promo->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Promo berhasil diperbarui',
            'data' => $promo,
        ]);
    }

    /**
     * Remove the specified promo
     * DELETE /api/admin/promos/{id}
     */
    public function destroy(int $id)
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
