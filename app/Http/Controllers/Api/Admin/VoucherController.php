<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVoucherRequest;
use App\Http\Requests\Admin\UpdateVoucherRequest;
use App\Models\Voucher;
use Illuminate\Http\JsonResponse;

class VoucherController extends Controller
{
    public function index(): JsonResponse
    {
        $vouchers = Voucher::orderByDesc('created_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Daftar voucher berhasil diambil',
            'data' => $vouchers,
        ]);
    }

    public function store(StoreVoucherRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $voucher = Voucher::create([
            'code' => $validated['code'],
            'type' => 'percentage',
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

    public function show(int $id): JsonResponse
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

    public function update(UpdateVoucherRequest $request, int $id): JsonResponse
    {
        $voucher = Voucher::find($id);

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher tidak ditemukan',
            ], 404);
        }

        $validated = $request->validated();
        $voucher->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Voucher berhasil diperbarui',
            'data' => $voucher,
        ]);
    }

    public function destroy(int $id): JsonResponse
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