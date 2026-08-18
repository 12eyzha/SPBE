<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePengajuanKtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('user') === true;
    }

    public function rules(): array
    {
        return [
            'jenis_permohonan' => [
                'required',
                'string',
                'in:baru,hilang,perpanjangan',
            ],

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

            'keperluan' => [
                'required',
                'string',
                'max:150',
            ],

            /*
            |--------------------------------------------------------------------------
            | Dokumen
            |--------------------------------------------------------------------------
            */

            'dokumen' => [
                'sometimes',
                'array',
            ],

            'dokumen.kk' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],

            'dokumen.akta_kelahiran' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],

            'dokumen.ijazah' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],

            'dokumen.pengantar_rt_rw' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],

            'dokumen.surat_kehilangan_polsek' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],

            'dokumen.ktp_lama' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /*
            |--------------------------------------------------------------------------
            | Validasi Usia Minimal 17 Tahun
            |--------------------------------------------------------------------------
            */
            if ($this->filled('tanggal_lahir')) {
                $tanggalLahir = Carbon::parse(
                    $this->input('tanggal_lahir')
                );

                if ($tanggalLahir->copy()->addYears(17)->isFuture()) {
                    $validator->errors()->add(
                        'tanggal_lahir',
                        'Pemohon harus sudah berusia minimal 17 tahun untuk mengajukan KTP.'
                    );
                }
            }

            $jenisPermohonan = $this->input('jenis_permohonan');

            /*
            |--------------------------------------------------------------------------
            | KTP BARU
            |--------------------------------------------------------------------------
            | Wajib:
            | - KK
            | - Akta Kelahiran ATAU Ijazah
            |--------------------------------------------------------------------------
            */
            if ($jenisPermohonan === 'baru') {
                if (! $this->hasFile('dokumen.kk')) {
                    $validator->errors()->add(
                        'dokumen.kk',
                        'Dokumen KK wajib diunggah untuk pengurusan KTP baru.'
                    );
                }

                if (
                    ! $this->hasFile('dokumen.akta_kelahiran') &&
                    ! $this->hasFile('dokumen.ijazah')
                ) {
                    $validator->errors()->add(
                        'dokumen.akta_kelahiran',
                        'Akta kelahiran atau ijazah wajib diunggah untuk pengurusan KTP baru.'
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | KTP PERPANJANGAN
            |--------------------------------------------------------------------------
            | Wajib:
            | - KTP lama
            | - KK
            | - Pengantar RT/RW
            |--------------------------------------------------------------------------
            */
            if ($jenisPermohonan === 'perpanjangan') {
                if (! $this->hasFile('dokumen.ktp_lama')) {
                    $validator->errors()->add(
                        'dokumen.ktp_lama',
                        'KTP lama wajib diunggah untuk pengurusan perpanjangan KTP.'
                    );
                }

                if (! $this->hasFile('dokumen.kk')) {
                    $validator->errors()->add(
                        'dokumen.kk',
                        'Dokumen KK wajib diunggah untuk pengurusan perpanjangan KTP.'
                    );
                }

                if (! $this->hasFile('dokumen.pengantar_rt_rw')) {
                    $validator->errors()->add(
                        'dokumen.pengantar_rt_rw',
                        'Pengantar RT/RW wajib diunggah untuk pengurusan perpanjangan KTP.'
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | KTP HILANG
            |--------------------------------------------------------------------------
            | Wajib:
            | - KK
            | - Surat Kehilangan Polsek
            | - Pengantar RT/RW
            |--------------------------------------------------------------------------
            */
            if ($jenisPermohonan === 'hilang') {
                if (! $this->hasFile('dokumen.kk')) {
                    $validator->errors()->add(
                        'dokumen.kk',
                        'Dokumen KK wajib diunggah untuk pengurusan KTP hilang.'
                    );
                }

                if (! $this->hasFile('dokumen.surat_kehilangan_polsek')) {
                    $validator->errors()->add(
                        'dokumen.surat_kehilangan_polsek',
                        'Surat kehilangan dari Polsek wajib diunggah untuk pengurusan KTP hilang.'
                    );
                }

                if (! $this->hasFile('dokumen.pengantar_rt_rw')) {
                    $validator->errors()->add(
                        'dokumen.pengantar_rt_rw',
                        'Pengantar RT/RW wajib diunggah untuk pengurusan KTP hilang.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'jenis_permohonan.required' => 'Jenis permohonan wajib dipilih.',
            'jenis_permohonan.in' => 'Jenis permohonan KTP tidak valid.',

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

            'keperluan.required' => 'Keperluan wajib diisi.',

            'dokumen.*.file' => 'Dokumen harus berupa file yang valid.',
            'dokumen.*.mimes' => 'Dokumen harus berupa PDF, JPG, JPEG, atau PNG.',
            'dokumen.*.max' => 'Ukuran setiap dokumen maksimal 5 MB.',
        ];
    }
}