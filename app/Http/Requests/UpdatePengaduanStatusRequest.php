<?php

namespace App\Http\Requests;

use App\Models\Pengaduan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePengaduanStatusRequest extends FormRequest
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
                    'diteruskan',
                    'selesai',
                ]),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $pengaduan = $this->route('pengaduan');

            if (! $pengaduan instanceof Pengaduan) {
                return;
            }

            $statusLama = $pengaduan->status;
            $statusBaru = $this->input('status');

            $transisiDiizinkan = [
                'terkirim' => [
                    'diteruskan',
                ],
                'diteruskan' => [
                    'selesai',
                ],
                'selesai' => [],
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
            'status.required' => 'Status pengaduan wajib dipilih.',
            'status.in' => 'Status pengaduan tidak valid.',
        ];
    }
}