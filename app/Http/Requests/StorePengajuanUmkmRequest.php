<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePengajuanUmkmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('user') === true;
    }

    public function rules(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Data UMKM
            |--------------------------------------------------------------------------
            */

            'nama_umkm' => [
                'required',
                'string',
                'max:150',
            ],

            'kategori_id' => [
                'required',
                'integer',
                'exists:kategori_umkm,id',
            ],

            'deskripsi_umkm' => [
                'required',
                'string',
                'max:5000',
            ],

            'harga_min' => [
                'required',
                'numeric',
                'min:0',
            ],

            'harga_max' => [
                'required',
                'numeric',
                'min:0',
            ],

            'alamat' => [
                'required',
                'string',
                'max:2000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Jam Operasional
            |--------------------------------------------------------------------------
            |
            | Jam operasional bersifat opsional.
            |
            | Diperbolehkan melewati tengah malam.
            |
            | Contoh valid:
            | 07:00 -> 18:00
            | 15:00 -> 01:00
            | 22:00 -> 02:00
            |
            | Yang tidak diperbolehkan hanya:
            | 15:00 -> 15:00
            |
            */

            'jam_buka_mulai' => [
                'nullable',
                'date_format:H:i',
            ],

            'jam_buka_selesai' => [
                'nullable',
                'date_format:H:i',
            ],

            /*
            |--------------------------------------------------------------------------
            | Kontak
            |--------------------------------------------------------------------------
            */

            'nomor_wa' => [
                'required',
                'string',
                'max:20',
            ],

            'link_ecommerce' => [
                'nullable',
                'url',
                'max:255',
            ],

            /*
            |--------------------------------------------------------------------------
            | Foto
            |--------------------------------------------------------------------------
            */

            'foto' => [
                'required',
                'array',
                'min:1',
                'max:5',
            ],

            'foto.*' => [
                'file',
                'mimes:jpg,jpeg,png',
                'max:5120',
            ],
        ];
    }

    public function withValidator(
        Validator $validator
    ): void {
        $validator->after(
            function (
                Validator $validator
            ) {
                /*
                |--------------------------------------------------------------------------
                | Validasi Harga
                |--------------------------------------------------------------------------
                */

                $hargaMin =
                    $this->input(
                        'harga_min'
                    );

                $hargaMax =
                    $this->input(
                        'harga_max'
                    );

                if (
                    is_numeric(
                        $hargaMin
                    ) &&
                    is_numeric(
                        $hargaMax
                    ) &&
                    (float) $hargaMin >
                    (float) $hargaMax
                ) {
                    $validator->errors()->add(
                        'harga_max',
                        'Harga maksimum tidak boleh lebih kecil dari harga minimum.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Validasi Jam Operasional
                |--------------------------------------------------------------------------
                |
                | Jangan membandingkan:
                |
                | $jamMulai >= $jamSelesai
                |
                | karena:
                |
                | 15:00 -> 01:00
                |
                | adalah jam operasional yang valid
                | apabila usaha buka sampai dini hari.
                |
                */

                $jamMulai =
                    $this->input(
                        'jam_buka_mulai'
                    );

                $jamSelesai =
                    $this->input(
                        'jam_buka_selesai'
                    );

                /*
                |--------------------------------------------------------------------------
                | Hanya tolak jika jam buka dan jam tutup sama.
                |--------------------------------------------------------------------------
                */

                if (
                    $jamMulai &&
                    $jamSelesai &&
                    $jamMulai ===
                    $jamSelesai
                ) {
                    $validator->errors()->add(
                        'jam_buka_selesai',
                        'Jam tutup tidak boleh sama dengan jam buka.'
                    );
                }
            }
        );
    }

    public function messages(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Data UMKM
            |--------------------------------------------------------------------------
            */

            'nama_umkm.required' =>
                'Nama UMKM wajib diisi.',

            'nama_umkm.max' =>
                'Nama UMKM maksimal 150 karakter.',

            'kategori_id.required' =>
                'Kategori UMKM wajib dipilih.',

            'kategori_id.exists' =>
                'Kategori UMKM tidak valid.',

            'deskripsi_umkm.required' =>
                'Deskripsi UMKM wajib diisi.',

            'deskripsi_umkm.max' =>
                'Deskripsi UMKM maksimal 5000 karakter.',

            'harga_min.required' =>
                'Harga minimum wajib diisi.',

            'harga_min.numeric' =>
                'Harga minimum harus berupa angka.',

            'harga_min.min' =>
                'Harga minimum tidak boleh kurang dari 0.',

            'harga_max.required' =>
                'Harga maksimum wajib diisi.',

            'harga_max.numeric' =>
                'Harga maksimum harus berupa angka.',

            'harga_max.min' =>
                'Harga maksimum tidak boleh kurang dari 0.',

            'alamat.required' =>
                'Alamat UMKM wajib diisi.',

            'alamat.max' =>
                'Alamat UMKM maksimal 2000 karakter.',

            /*
            |--------------------------------------------------------------------------
            | Jam Operasional
            |--------------------------------------------------------------------------
            */

            'jam_buka_mulai.date_format' =>
                'Format jam buka harus HH:MM.',

            'jam_buka_selesai.date_format' =>
                'Format jam tutup harus HH:MM.',

            /*
            |--------------------------------------------------------------------------
            | Kontak
            |--------------------------------------------------------------------------
            */

            'nomor_wa.required' =>
                'Nomor WhatsApp wajib diisi.',

            'nomor_wa.max' =>
                'Nomor WhatsApp maksimal 20 karakter.',

            'link_ecommerce.url' =>
                'Link e-commerce harus berupa URL yang valid.',

            'link_ecommerce.max' =>
                'Link e-commerce maksimal 255 karakter.',

            /*
            |--------------------------------------------------------------------------
            | Foto
            |--------------------------------------------------------------------------
            */

            'foto.required' =>
                'Minimal 1 foto UMKM wajib diunggah.',

            'foto.array' =>
                'Format data foto tidak valid.',

            'foto.min' =>
                'Minimal 1 foto UMKM wajib diunggah.',

            'foto.max' =>
                'Maksimal 5 foto UMKM dapat diunggah.',

            'foto.*.file' =>
                'Foto UMKM harus berupa file yang valid.',

            'foto.*.mimes' =>
                'Foto UMKM harus berupa JPG, JPEG, atau PNG.',

            'foto.*.max' =>
                'Ukuran setiap foto maksimal 5 MB.',
        ];
    }
}