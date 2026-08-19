<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePengaduanResponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin', 'superadmin') === true;
    }

    public function rules(): array
    {
        return [
            'respon' => [
                'required',
                'string',
                'max:5000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'respon.required' => 'Respons petugas wajib diisi.',
            'respon.string' => 'Respons petugas harus berupa teks.',
            'respon.max' => 'Respons petugas maksimal 5000 karakter.',
        ];
    }
}