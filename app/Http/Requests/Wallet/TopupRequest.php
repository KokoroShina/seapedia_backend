<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class TopupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:10000',
            'payment_method' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Jumlah top-up wajib diisi',
            'amount.numeric' => 'Jumlah top-up harus berupa angka',
            'amount.min' => 'Minimal top-up adalah Rp 10.000',
            'payment_method.required' => 'Metode pembayaran wajib dipilih',
        ];
    }
}