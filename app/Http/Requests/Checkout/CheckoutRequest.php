<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address_id' => 'required|exists:addresses,id',
            'delivery_method' => 'required|in:instant,next_day,regular',
            'voucher_code' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'address_id.required' => 'Alamat pengiriman wajib dipilih',
            'address_id.exists' => 'Alamat tidak ditemukan',
            'delivery_method.required' => 'Metode pengiriman wajib dipilih',
            'delivery_method.in' => 'Metode pengiriman tidak valid',
            'voucher_code.max' => 'Kode voucher maksimal 50 karakter',
        ];
    }
}