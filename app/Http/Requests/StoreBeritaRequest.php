<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBeritaRequest extends FormRequest
{
    /**
     * Hanya Admin / Super Admin yang dapat membuat berita.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole(
            'admin',
            'superadmin'
        ) === true;
    }

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Judul
            |--------------------------------------------------------------------------
            */

            'judul' => [
                'required',
                'string',
                'max:200',
            ],

            /*
            |--------------------------------------------------------------------------
            | Isi Berita
            |--------------------------------------------------------------------------
            */

            'isi' => [
                'required',
                'string',
            ],

            /*
            |--------------------------------------------------------------------------
            | Thumbnail
            |--------------------------------------------------------------------------
            |
            | Thumbnail bersifat opsional.
            | Maksimal 5 MB.
            |
            */

            'thumbnail' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'status' => [
                'required',
                'in:draft,published',
            ],
        ];
    }

    /**
     * Validasi tambahan.
     */
    public function withValidator(
        Validator $validator
    ): void {
        $validator->after(
            function (
                Validator $validator
            ) {
                /*
                |--------------------------------------------------------------------------
                | Published At Tidak Dikirim Dari Client
                |--------------------------------------------------------------------------
                |
                | published_at akan ditentukan controller:
                |
                | published => now()
                | draft     => null
                |
                */

                if (
                    $this->filled(
                        'published_at'
                    )
                ) {
                    $validator->errors()->add(
                        'published_at',
                        'Tanggal publikasi ditentukan otomatis oleh sistem.'
                    );
                }
            }
        );
    }

    /**
     * Custom messages.
     */
    public function messages(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Judul
            |--------------------------------------------------------------------------
            */

            'judul.required' =>
                'Judul berita wajib diisi.',

            'judul.string' =>
                'Judul berita harus berupa teks.',

            'judul.max' =>
                'Judul berita maksimal 200 karakter.',

            /*
            |--------------------------------------------------------------------------
            | Isi
            |--------------------------------------------------------------------------
            */

            'isi.required' =>
                'Isi berita wajib diisi.',

            'isi.string' =>
                'Isi berita harus berupa teks.',

            /*
            |--------------------------------------------------------------------------
            | Thumbnail
            |--------------------------------------------------------------------------
            */

            'thumbnail.file' =>
                'Thumbnail harus berupa file yang valid.',

            'thumbnail.mimes' =>
                'Thumbnail harus berupa JPG, JPEG, PNG, atau WEBP.',

            'thumbnail.max' =>
                'Ukuran thumbnail maksimal 5 MB.',

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'status.required' =>
                'Status berita wajib dipilih.',

            'status.in' =>
                'Status berita hanya dapat berupa draft atau published.',

            /*
            |--------------------------------------------------------------------------
            | Published At
            |--------------------------------------------------------------------------
            */

            'published_at' =>
                'Tanggal publikasi ditentukan otomatis oleh sistem.',
        ];
    }
}