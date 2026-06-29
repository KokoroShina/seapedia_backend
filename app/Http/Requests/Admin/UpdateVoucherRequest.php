<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $voucherId = $this->route('voucher') ?? $this->route('id');

        return [
            'code' => 'sometimes|required|string|max:50|unique:vouchers,code,' . $voucherId,
            'value' => 'sometimes|required|numeric|min:1|max:100',
            'expired_at' => 'sometimes|required|date|after:now',
            'max_usage' => 'sometimes|required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode voucher wajib diisi',
            'code.unique' => 'Kode voucher sudah digunakan',
            'code.max' => 'Kode voucher maksimal 50 karakter',
            'value.required' => 'Nilai diskon wajib diisi',
            'value.numeric' => 'Nilai diskon harus berupa angka',
            'value.min' => 'Nilai diskon minimal 1%',
            'value.max' => 'Nilai diskon maksimal 100%',
            'expired_at.required' => 'Tanggal kadaluarsa wajib diisi',
            'expired_at.date' => 'Format tanggal tidak valid',
            'expired_at.after' => 'Tanggal kadaluarsa harus setelah waktu sekarang',
            'max_usage.required' => 'Batas penggunaan wajib diisi',
            'max_usage.integer' => 'Batas penggunaan harus berupa angka bulat',
            'max_usage.min' => 'Batas penggunaan minimal 1',
        ];
    }
}