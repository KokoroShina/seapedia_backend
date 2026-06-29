<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdvanceTimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hours' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'hours.required' => 'Jumlah jam wajib diisi',
            'hours.integer' => 'Jumlah jam harus berupa angka bulat',
            'hours.min' => 'Jumlah jam minimal 1',
        ];
    }
}