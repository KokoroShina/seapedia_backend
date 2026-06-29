<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePromoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'value' => 'sometimes|required|numeric|min:1|max:100',
            'min_purchase' => 'sometimes|required|numeric|min:0',
            'expired_at' => 'sometimes|required|date|after:now',
        ];
    }

    public function messages(): array
    {
        return [
            'value.required' => 'Nilai diskon wajib diisi',
            'value.numeric' => 'Nilai diskon harus berupa angka',
            'value.min' => 'Nilai diskon minimal 1%',
            'value.max' => 'Nilai diskon maksimal 100%',
            'min_purchase.required' => 'Minimal pembelian wajib diisi',
            'min_purchase.numeric' => 'Minimal pembelian harus berupa angka',
            'min_purchase.min' => 'Minimal pembelian tidak boleh negatif',
            'expired_at.required' => 'Tanggal kadaluarsa wajib diisi',
            'expired_at.date' => 'Format tanggal tidak valid',
            'expired_at.after' => 'Tanggal kadaluarsa harus setelah waktu sekarang',
        ];
    }
}