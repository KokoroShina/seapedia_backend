<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    /**
     * Display a listing of all vouchers (paginated, newest first)
     * GET /api/admin/vouchers
     */
    public function index()
    {
        $vouchers = Voucher::orderByDesc('created_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Daftar voucher berhasil diambil',
            'data' => $vouchers,
        ]);
    }

    /**
     * Store a newly created voucher
     * POST /api/admin/vouchers
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:vouchers,code',
            'value' => 'required|numeric|min:1|max:100',
            'expired_at' => 'required|date|after:now',
            'max_usage' => 'required|integer|min:1',
        ]);

        $voucher = Voucher::create([
            'code' => $validated['code'],
            'type' => 'percentage', // Selalu percentage untuk saat ini
            'value' => $validated['value'],
            'expired_at' => $validated['expired_at'],
            'max_usage' => $validated['max_usage'],
            'used_count' => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Voucher berhasil dibuat',
            'data' => $voucher,
        ], 201);
    }

    /**
     * Display the specified voucher
     * GET /api/admin/vouchers/{id}
     */
    public function show(int $id)
    {
        $voucher = Voucher::find($id);

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail voucher berhasil diambil',
            'data' => $voucher,
        ]);
    }

    /**
     * Update the specified voucher
     * PUT /api/admin/vouchers/{id}
     */
    public function update(Request $request, int $id)
    {
        $voucher = Voucher::find($id);

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher tidak ditemukan',
            ], 404);
        }

        $validated = $request->validate([
            'code' => 'sometimes|required|string|max:50|unique:vouchers,code,' . $id,
            'value' => 'sometimes|required|numeric|min:1|max:100',
            'expired_at' => 'sometimes|required|date|after:now',
            'max_usage' => 'sometimes|required|integer|min:1',
        ]);

        $voucher->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Voucher berhasil diperbarui',
            'data' => $voucher,
        ]);
    }

    /**
     * Remove the specified voucher
     * DELETE /api/admin/vouchers/{id}
     */
    public function destroy(int $id)
    {
        $voucher = Voucher::find($id);

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher tidak ditemukan',
            ], 404);
        }

        $voucher->delete();

        return response()->json([
            'success' => true,
            'message' => 'Voucher berhasil dihapus',
        ]);
    }
}
