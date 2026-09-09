<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateBeritaRequest extends FormRequest
{
    /**
     * Hanya Admin / Super Admin yang dapat mengubah berita.
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
            | Thumbnail baru bersifat opsional.
            | Jika tidak dikirim, thumbnail lama tetap digunakan.
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
                | Published At
                |--------------------------------------------------------------------------
                |
                | Client tidak boleh mengubah published_at secara manual.
                | Nilainya ditentukan otomatis oleh controller.
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

                /*
                |--------------------------------------------------------------------------
                | Slug
                |--------------------------------------------------------------------------
                |
                | Slug juga tidak boleh dikirim manual.
                | Controller akan membuat slug dari judul.
                |
                */

                if (
                    $this->filled(
                        'slug'
                    )
                ) {
                    $validator->errors()->add(
                        'slug',
                        'Slug berita dibuat otomatis dari judul.'
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
            | Field Otomatis
            |--------------------------------------------------------------------------
            */

            'published_at' =>
                'Tanggal publikasi ditentukan otomatis oleh sistem.',

            'slug' =>
                'Slug berita dibuat otomatis dari judul.',
        ];
    }
}