<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    public function index(): JsonResponse
    {
        $addresses = Auth::user()->addresses()->orderByDesc('is_default')->orderByDesc('id')->get();
        return response()->json(['success' => true, 'data' => $addresses]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label' => 'nullable|string|max:50',
            'address' => 'required|string',
            'is_default' => 'boolean',
        ]);

        if (!empty($validated['is_default'])) {
            Auth::user()->addresses()->update(['is_default' => false]);
        }

        $address = Auth::user()->addresses()->create($validated);
        return response()->json(['success' => true, 'data' => $address], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $address = Auth::user()->addresses()->findOrFail($id);

        $validated = $request->validate([
            'label' => 'nullable|string|max:50',
            'address' => 'required|string',
            'is_default' => 'boolean',
        ]);

        if (!empty($validated['is_default'])) {
            Auth::user()->addresses()->where('id', '!=', $id)->update(['is_default' => false]);
        }

        $address->update($validated);
        return response()->json(['success' => true, 'data' => $address]);
    }

    public function destroy(string $id): JsonResponse
    {
        $address = Auth::user()->addresses()->findOrFail($id);
        $address->delete();
        return response()->json(['success' => true, 'message' => 'Address deleted']);
    }

    public function setDefault(string $id): JsonResponse
    {
        $address = Auth::user()->addresses()->findOrFail($id);
        Auth::user()->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);
        return response()->json(['success' => true, 'data' => $address]);
    }
}
