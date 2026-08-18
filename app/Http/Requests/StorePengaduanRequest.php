<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePengaduanRequest extends FormRequest
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
            | Identitas Pelapor
            |--------------------------------------------------------------------------
            */

            'nama' => [
                'required',
                'string',
                'max:100',
            ],

            'nomor' => [
                'required',
                'string',
                'max:20',
            ],

            /*
            |--------------------------------------------------------------------------
            | Informasi Pengaduan
            |--------------------------------------------------------------------------
            */

            'subjek' => [
                'required',
                'string',
                'max:150',
            ],

            'keterangan' => [
                'required',
                'string',
            ],

            'lokasi' => [
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

            /*
            |--------------------------------------------------------------------------
            | Foto Bukti
            |--------------------------------------------------------------------------
            */

            'foto_bukti' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png',
                'max:5120',
            ],

            /*
            |--------------------------------------------------------------------------
            | Dokumen Pendukung
            |--------------------------------------------------------------------------
            |
            | Bisa lebih dari satu file.
            | Masing-masing maksimal 5 MB.
            |
            */

            'dokumen' => [
                'nullable',
                'array',
                'max:5',
            ],

            'dokumen.*' => [
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Identitas Pelapor
            |--------------------------------------------------------------------------
            */

            'nama.required' => 'Nama lengkap wajib diisi.',
            'nama.max' => 'Nama lengkap maksimal 100 karakter.',

            'nomor.required' => 'Nomor telepon wajib diisi.',
            'nomor.max' => 'Nomor telepon maksimal 20 karakter.',

            /*
            |--------------------------------------------------------------------------
            | Informasi Pengaduan
            |--------------------------------------------------------------------------
            */

            'subjek.required' => 'Subjek pengaduan wajib diisi.',
            'subjek.max' => 'Subjek pengaduan maksimal 150 karakter.',

            'keterangan.required' => 'Deskripsi masalah wajib diisi.',

            'lokasi.required' => 'Lokasi kejadian wajib diisi.',

            'rt.required' => 'RT wajib diisi.',
            'rw.required' => 'RW wajib diisi.',

            /*
            |--------------------------------------------------------------------------
            | Foto Bukti
            |--------------------------------------------------------------------------
            */

            'foto_bukti.file' => 'Foto bukti harus berupa file yang valid.',
            'foto_bukti.mimes' => 'Foto bukti harus berupa JPG, JPEG, atau PNG.',
            'foto_bukti.max' => 'Ukuran foto bukti maksimal 5 MB.',

            /*
            |--------------------------------------------------------------------------
            | Dokumen Pendukung
            |--------------------------------------------------------------------------
            */

            'dokumen.array' => 'Format dokumen pendukung tidak valid.',
            'dokumen.max' => 'Maksimal 5 dokumen pendukung dapat diunggah.',

            'dokumen.*.file' => 'Dokumen pendukung harus berupa file yang valid.',
            'dokumen.*.mimes' => 'Dokumen pendukung harus berupa PDF, JPG, JPEG, atau PNG.',
            'dokumen.*.max' => 'Ukuran setiap dokumen pendukung maksimal 5 MB.',
        ];
    }
}