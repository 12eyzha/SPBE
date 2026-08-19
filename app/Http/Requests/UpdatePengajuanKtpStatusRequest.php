<?php

namespace App\Http\Requests;

use App\Models\PengajuanKtp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePengajuanKtpStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin', 'superadmin') === true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([
                    'diproses',
                    'disetujui',
                    'ditolak',
                ]),
            ],

            'catatan_admin' => [
                'nullable',
                'string',
                'max:5000',
                Rule::requiredIf(
                    fn () => $this->input('status') === 'ditolak'
                ),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $pengajuan = $this->route('pengajuanKtp');

            if (! $pengajuan instanceof PengajuanKtp) {
                return;
            }

            $statusLama = $pengajuan->status;
            $statusBaru = $this->input('status');

            $transisiDiizinkan = [
                'menunggu_verifikasi' => [
                    'diproses',
                    'disetujui',
                    'ditolak',
                ],
                'diproses' => [
                    'disetujui',
                    'ditolak',
                ],
                'disetujui' => [],
                'ditolak' => [],
            ];

            if (
                ! in_array(
                    $statusBaru,
                    $transisiDiizinkan[$statusLama] ?? [],
                    true
                )
            ) {
                $validator->errors()->add(
                    'status',
                    "Status tidak dapat diubah dari {$statusLama} menjadi {$statusBaru}."
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status pengajuan wajib dipilih.',
            'status.in' => 'Status pengajuan tidak valid.',

            'catatan_admin.required' => 'Catatan wajib diisi ketika pengajuan ditolak.',
            'catatan_admin.string' => 'Catatan admin harus berupa teks.',
            'catatan_admin.max' => 'Catatan admin maksimal 5000 karakter.',
        ];
    }
}