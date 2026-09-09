<?php

namespace App\Http\Requests;

use App\Models\KategoriUmkm;
use App\Models\PengajuanUmkm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePengajuanUmkmRequest extends FormRequest
{
    /**
     * Authorization.
     *
     * User hanya boleh mengubah UMKM miliknya sendiri
     * dan UMKM yang belum di-soft-delete.
     */
    public function authorize(): bool
    {
        $pengajuanUmkm =
            $this->route(
                'pengajuanUmkm'
            );

        return $this->user()?->hasRole('user') === true
            && $pengajuanUmkm instanceof PengajuanUmkm
            && $pengajuanUmkm->user_id === $this->user()->id
            && ! $pengajuanUmkm->trashed();
    }

    /**
     * Validation rules.
     */
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
            | Jam boleh melewati tengah malam.
            |
            | Contoh:
            | 07:00 -> 18:00 ✅
            | 15:00 -> 01:00 ✅
            | 22:00 -> 02:00 ✅
            |
            | Yang tidak diperbolehkan hanya jam buka dan tutup
            | yang persis sama.
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
            | Foto Lama
            |--------------------------------------------------------------------------
            |
            | ID foto lama yang ingin dipertahankan.
            |
            */

            'existing_foto_ids' => [
                'nullable',
                'array',
            ],

            'existing_foto_ids.*' => [
                'integer',
                'distinct',
            ],

            /*
            |--------------------------------------------------------------------------
            | Foto Baru
            |--------------------------------------------------------------------------
            */

            'foto' => [
                'nullable',
                'array',
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

    /**
     * Additional validation.
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
                | Ambil Pengajuan
                |--------------------------------------------------------------------------
                */

                $pengajuanUmkm =
                    $this->route(
                        'pengajuanUmkm'
                    );

                if (
                    ! $pengajuanUmkm instanceof PengajuanUmkm
                ) {
                    return;
                }

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
                    is_numeric($hargaMin) &&
                    is_numeric($hargaMax) &&
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
                | karena hal tersebut akan salah untuk usaha
                | yang buka melewati tengah malam.
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
                | Jam buka dan tutup tidak boleh sama
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

                /*
                |--------------------------------------------------------------------------
                | Kategori Harus Aktif
                |--------------------------------------------------------------------------
                */

                $kategoriId =
                    $this->input(
                        'kategori_id'
                    );

                if (
                    $kategoriId &&
                    ! KategoriUmkm::query()
                        ->whereKey(
                            $kategoriId
                        )
                        ->where(
                            'is_active',
                            true
                        )
                        ->exists()
                ) {
                    $validator->errors()->add(
                        'kategori_id',
                        'Kategori UMKM sedang tidak aktif.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Existing Photo IDs
                |--------------------------------------------------------------------------
                */

                $existingFotoIds =
                    $this->input(
                        'existing_foto_ids',
                        []
                    );

                if (
                    ! is_array(
                        $existingFotoIds
                    )
                ) {
                    $existingFotoIds = [];
                }

                /*
                |--------------------------------------------------------------------------
                | Pastikan Semua Foto Lama Milik UMKM
                |--------------------------------------------------------------------------
                */

                if (
                    count(
                        $existingFotoIds
                    ) > 0
                ) {
                    $validPhotoIds =
                        $pengajuanUmkm
                            ->foto()
                            ->whereIn(
                                'id',
                                $existingFotoIds
                            )
                            ->pluck('id')
                            ->map(
                                fn ($id) =>
                                    (int) $id
                            )
                            ->all();

                    foreach (
                        $existingFotoIds as $photoId
                    ) {
                        if (
                            ! in_array(
                                (int) $photoId,
                                $validPhotoIds,
                                true
                            )
                        ) {
                            $validator->errors()->add(
                                'existing_foto_ids',
                                'Terdapat foto yang tidak valid untuk UMKM ini.'
                            );

                            break;
                        }
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Foto Baru
                |--------------------------------------------------------------------------
                */

                $newPhotos =
                    $this->file(
                        'foto',
                        []
                    );

                if (
                    ! is_array(
                        $newPhotos
                    )
                ) {
                    $newPhotos = [];
                }

                $existingPhotoCount =
                    count(
                        $existingFotoIds
                    );

                $newPhotoCount =
                    count(
                        $newPhotos
                    );

                $totalPhotos =
                    $existingPhotoCount +
                    $newPhotoCount;

                /*
                |--------------------------------------------------------------------------
                | Maksimal 5 Foto
                |--------------------------------------------------------------------------
                */

                if (
                    $totalPhotos >
                    5
                ) {
                    $validator->errors()->add(
                        'foto',
                        'Jumlah foto UMKM setelah diperbarui maksimal 5 foto.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Minimal 1 Foto
                |--------------------------------------------------------------------------
                */

                if (
                    $totalPhotos <
                    1
                ) {
                    $validator->errors()->add(
                        'foto',
                        'Minimal 1 foto UMKM harus tersedia.'
                    );
                }
            }
        );
    }

    /**
     * Custom validation messages.
     */
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

            'nama_umkm.string' =>
                'Nama UMKM harus berupa teks.',

            'nama_umkm.max' =>
                'Nama UMKM maksimal 150 karakter.',

            'kategori_id.required' =>
                'Kategori UMKM wajib dipilih.',

            'kategori_id.integer' =>
                'Kategori UMKM tidak valid.',

            'kategori_id.exists' =>
                'Kategori UMKM tidak ditemukan.',

            'deskripsi_umkm.required' =>
                'Deskripsi UMKM wajib diisi.',

            'deskripsi_umkm.string' =>
                'Deskripsi UMKM harus berupa teks.',

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

            'alamat.string' =>
                'Alamat UMKM harus berupa teks.',

            'alamat.max' =>
                'Alamat UMKM maksimal 2000 karakter.',

            /*
            |--------------------------------------------------------------------------
            | Jam
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

            'nomor_wa.string' =>
                'Nomor WhatsApp harus berupa teks.',

            'nomor_wa.max' =>
                'Nomor WhatsApp maksimal 20 karakter.',

            'link_ecommerce.url' =>
                'Link e-commerce harus berupa URL yang valid.',

            'link_ecommerce.max' =>
                'Link e-commerce maksimal 255 karakter.',

            /*
            |--------------------------------------------------------------------------
            | Foto Lama
            |--------------------------------------------------------------------------
            */

            'existing_foto_ids.array' =>
                'Format foto lama tidak valid.',

            'existing_foto_ids.*.integer' =>
                'ID foto lama harus berupa angka.',

            'existing_foto_ids.*.distinct' =>
                'Foto lama tidak boleh dipilih lebih dari satu kali.',

            /*
            |--------------------------------------------------------------------------
            | Foto Baru
            |--------------------------------------------------------------------------
            */

            'foto.array' =>
                'Format foto baru tidak valid.',

            'foto.max' =>
                'Maksimal 5 foto baru dapat diunggah.',

            'foto.*.required' =>
                'Foto UMKM wajib diunggah.',

            'foto.*.file' =>
                'Foto UMKM harus berupa file yang valid.',

            'foto.*.mimes' =>
                'Foto UMKM harus berupa JPG, JPEG, atau PNG.',

            'foto.*.max' =>
                'Ukuran setiap foto maksimal 5 MB.',
        ];
    }
}