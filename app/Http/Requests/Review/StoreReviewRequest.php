<?php

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'Rating wajib diisi',
            'rating.integer' => 'Rating harus berupa angka',
            'rating.min' => 'Rating minimal 1',
            'rating.max' => 'Rating maksimal 5',
            'comment.required' => 'Komentar wajib diisi',
            'comment.max' => 'Komentar maksimal 1000 karakter',
        ];
    }

    /**
     * Sanitasi input comment untuk prevent XSS.
     * Menggunakan strip_tags() untuk menghapus seluruh HTML/script tag.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('comment')) {
            $this->merge([
                'comment' => strip_tags($this->input('comment')),
            ]);
        }
    }
}