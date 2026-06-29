<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SwitchRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => 'required|in:buyer,seller,driver',
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'Role wajib dipilih',
            'role.in' => 'Role tidak valid',
        ];
    }
}