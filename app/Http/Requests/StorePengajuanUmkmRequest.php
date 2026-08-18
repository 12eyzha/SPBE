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
            ],

            'jam_buka_mulai' => [
                'nullable',
                'date_format:H:i',
            ],

            'jam_buka_selesai' => [
                'nullable',
                'date_format:H:i',
            ],

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
            | Foto Produk
            |--------------------------------------------------------------------------
            |
            | FE:
            | - minimal 1 foto
            | - maksimal 5 foto
            | - setiap file maksimal 5 MB
            | - hanya JPG/JPEG/PNG
            |
            */

            'foto' => [
                'required',
                'array',
                'min:1',
                'max:5',
            ],

            'foto.*' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png',
                'max:5120',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /*
            |--------------------------------------------------------------------------
            | Validasi Harga
            |--------------------------------------------------------------------------
            */
            $hargaMin = $this->input('harga_min');
            $hargaMax = $this->input('harga_max');

            if (
                is_numeric($hargaMin) &&
                is_numeric($hargaMax) &&
                (float) $hargaMin > (float) $hargaMax
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
            */
            $jamMulai = $this->input('jam_buka_mulai');
            $jamSelesai = $this->input('jam_buka_selesai');

            if ($jamMulai && $jamSelesai && $jamMulai >= $jamSelesai) {
                $validator->errors()->add(
                    'jam_buka_selesai',
                    'Jam tutup harus lebih besar dari jam buka.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Validasi Jumlah Foto
            |--------------------------------------------------------------------------
            |
            | Rule array:min/max sudah memvalidasi jumlah item.
            | Pengecekan tambahan ini memastikan hanya file yang benar-benar
            | diterima sebagai foto.
            |
            */
            $foto = $this->file('foto', []);

            if (is_array($foto) && count($foto) > 5) {
                $validator->errors()->add(
                    'foto',
                    'Maksimal 5 foto yang dapat diunggah.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Data UMKM
            |--------------------------------------------------------------------------
            */

            'nama_umkm.required' => 'Nama UMKM wajib diisi.',
            'nama_umkm.max' => 'Nama UMKM maksimal 150 karakter.',

            'kategori_id.required' => 'Kategori UMKM wajib dipilih.',
            'kategori_id.exists' => 'Kategori UMKM tidak valid.',

            'deskripsi_umkm.required' => 'Deskripsi UMKM wajib diisi.',

            'harga_min.required' => 'Harga minimum wajib diisi.',
            'harga_min.numeric' => 'Harga minimum harus berupa angka.',
            'harga_min.min' => 'Harga minimum tidak boleh kurang dari 0.',

            'harga_max.required' => 'Harga maksimum wajib diisi.',
            'harga_max.numeric' => 'Harga maksimum harus berupa angka.',
            'harga_max.min' => 'Harga maksimum tidak boleh kurang dari 0.',

            'alamat.required' => 'Alamat UMKM wajib diisi.',

            'jam_buka_mulai.date_format' => 'Format jam buka harus HH:MM.',
            'jam_buka_selesai.date_format' => 'Format jam tutup harus HH:MM.',

            'nomor_wa.required' => 'Nomor WhatsApp wajib diisi.',
            'nomor_wa.max' => 'Nomor WhatsApp maksimal 20 karakter.',

            'link_ecommerce.url' => 'Link e-commerce harus berupa URL yang valid.',
            'link_ecommerce.max' => 'Link e-commerce maksimal 255 karakter.',

            /*
            |--------------------------------------------------------------------------
            | Foto
            |--------------------------------------------------------------------------
            */

            'foto.required' => 'Minimal 1 foto UMKM wajib diunggah.',
            'foto.array' => 'Format data foto tidak valid.',
            'foto.min' => 'Minimal 1 foto UMKM wajib diunggah.',
            'foto.max' => 'Maksimal 5 foto UMKM dapat diunggah.',

            'foto.*.required' => 'Foto UMKM wajib diunggah.',
            'foto.*.file' => 'Foto UMKM harus berupa file yang valid.',
            'foto.*.mimes' => 'Foto UMKM harus berupa JPG, JPEG, atau PNG.',
            'foto.*.max' => 'Ukuran setiap foto maksimal 5 MB.',
        ];
    }
}