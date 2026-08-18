<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePengajuanSkuRequest extends FormRequest
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
            | Data Pemohon
            |--------------------------------------------------------------------------
            */

            'nik' => [
                'required',
                'digits:16',
            ],

            'nama_lengkap' => [
                'required',
                'string',
                'max:100',
            ],

            'nomor_kk' => [
                'required',
                'digits:16',
            ],

            'tempat_lahir' => [
                'required',
                'string',
                'max:100',
            ],

            'tanggal_lahir' => [
                'required',
                'date',
                'before_or_equal:today',
            ],

            'jenis_kelamin' => [
                'required',
                'in:L,P',
            ],

            'alamat' => [
                'required',
                'string',
            ],

            'rt' => [
                'required',
                'string',
                'max:5',
            ],

            'rw' => [
                'required',
                'string',
                'max:5',
            ],

            'kode_pos' => [
                'required',
                'string',
                'max:5',
            ],

            /*
            |--------------------------------------------------------------------------
            | Data Usaha
            |--------------------------------------------------------------------------
            */

            'nama_usaha' => [
                'required',
                'string',
                'max:150',
            ],

            'jenis_usaha' => [
                'required',
                'string',
                'max:100',
            ],

            'deskripsi_usaha' => [
                'required',
                'string',
            ],

            'alamat_usaha' => [
                'required',
                'string',
            ],

            'rt_usaha' => [
                'required',
                'string',
                'max:5',
            ],

            'rw_usaha' => [
                'required',
                'string',
                'max:5',
            ],

            'lama_menjalankan_usaha' => [
                'required',
                'integer',
                'min:0',
                'max:999',
            ],

            'perkiraan_penghasilan_per_bulan' => [
                'required',
                'string',
                'max:100',
            ],

            /*
            |--------------------------------------------------------------------------
            | Dokumen Pendukung
            |--------------------------------------------------------------------------
            */

            'dokumen' => [
                'required',
                'array',
            ],

            'dokumen.ktp' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],

            'dokumen.kk' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],

            'dokumen.foto_tempat_usaha' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Data Pemohon
            |--------------------------------------------------------------------------
            */

            'nik.required' => 'NIK wajib diisi.',
            'nik.digits' => 'NIK harus terdiri dari 16 digit.',

            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',

            'nomor_kk.required' => 'Nomor KK wajib diisi.',
            'nomor_kk.digits' => 'Nomor KK harus terdiri dari 16 digit.',

            'tempat_lahir.required' => 'Tempat lahir wajib diisi.',

            'tanggal_lahir.required' => 'Tanggal lahir wajib diisi.',
            'tanggal_lahir.date' => 'Tanggal lahir tidak valid.',
            'tanggal_lahir.before_or_equal' => 'Tanggal lahir tidak boleh melebihi hari ini.',

            'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
            'jenis_kelamin.in' => 'Jenis kelamin tidak valid.',

            'alamat.required' => 'Alamat wajib diisi.',
            'rt.required' => 'RT wajib diisi.',
            'rw.required' => 'RW wajib diisi.',
            'kode_pos.required' => 'Kode pos wajib diisi.',

            /*
            |--------------------------------------------------------------------------
            | Data Usaha
            |--------------------------------------------------------------------------
            */

            'nama_usaha.required' => 'Nama usaha wajib diisi.',
            'jenis_usaha.required' => 'Jenis usaha wajib dipilih.',
            'deskripsi_usaha.required' => 'Deskripsi usaha wajib diisi.',
            'alamat_usaha.required' => 'Alamat usaha wajib diisi.',
            'rt_usaha.required' => 'RT tempat usaha wajib diisi.',
            'rw_usaha.required' => 'RW tempat usaha wajib diisi.',

            'lama_menjalankan_usaha.required' => 'Lama menjalankan usaha wajib diisi.',
            'lama_menjalankan_usaha.integer' => 'Lama menjalankan usaha harus berupa angka.',
            'lama_menjalankan_usaha.min' => 'Lama menjalankan usaha tidak boleh kurang dari 0.',
            'lama_menjalankan_usaha.max' => 'Lama menjalankan usaha tidak valid.',

            'perkiraan_penghasilan_per_bulan.required' => 'Perkiraan penghasilan per bulan wajib dipilih.',

            /*
            |--------------------------------------------------------------------------
            | Dokumen
            |--------------------------------------------------------------------------
            */

            'dokumen.required' => 'Dokumen pendukung wajib diunggah.',

            'dokumen.ktp.required' => 'KTP wajib diunggah.',
            'dokumen.ktp.file' => 'File KTP tidak valid.',
            'dokumen.ktp.mimes' => 'KTP harus berupa PDF, JPG, JPEG, atau PNG.',
            'dokumen.ktp.max' => 'Ukuran KTP maksimal 5 MB.',

            'dokumen.kk.required' => 'KK wajib diunggah.',
            'dokumen.kk.file' => 'File KK tidak valid.',
            'dokumen.kk.mimes' => 'KK harus berupa PDF, JPG, JPEG, atau PNG.',
            'dokumen.kk.max' => 'Ukuran KK maksimal 5 MB.',

            'dokumen.foto_tempat_usaha.required' => 'Foto tempat usaha wajib diunggah.',
            'dokumen.foto_tempat_usaha.file' => 'File foto tempat usaha tidak valid.',
            'dokumen.foto_tempat_usaha.mimes' => 'Foto tempat usaha harus berupa JPG, JPEG, atau PNG.',
            'dokumen.foto_tempat_usaha.max' => 'Ukuran foto tempat usaha maksimal 5 MB.',
        ];
    }
}