<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminPengajuanSkuResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            /*
            |--------------------------------------------------------------------------
            | Data Pemohon
            |--------------------------------------------------------------------------
            |
            | Resource ini khusus Admin/Super Admin.
            | NIK dan Nomor KK ditampilkan penuh.
            |
            */

            'nik' => $this->nik,
            'nama_lengkap' => $this->nama_lengkap,
            'nomor_kk' => $this->nomor_kk,

            'tempat_lahir' => $this->tempat_lahir,
            'tanggal_lahir' => $this->tanggal_lahir?->format('Y-m-d'),
            'jenis_kelamin' => $this->jenis_kelamin,

            /*
            |--------------------------------------------------------------------------
            | Alamat Pemohon
            |--------------------------------------------------------------------------
            */

            'alamat' => $this->alamat,
            'rt' => $this->rt,
            'rw' => $this->rw,
            'kode_pos' => $this->kode_pos,

            /*
            |--------------------------------------------------------------------------
            | Data Usaha
            |--------------------------------------------------------------------------
            */

            'nama_usaha' => $this->nama_usaha,
            'jenis_usaha' => $this->jenis_usaha,
            'deskripsi_usaha' => $this->deskripsi_usaha,
            'alamat_usaha' => $this->alamat_usaha,
            'rt_usaha' => $this->rt_usaha,
            'rw_usaha' => $this->rw_usaha,
            'lama_menjalankan_usaha' => $this->lama_menjalankan_usaha,
            'perkiraan_penghasilan_per_bulan' => $this->perkiraan_penghasilan_per_bulan,

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'status' => $this->status,
            'catatan_admin' => $this->catatan_admin,
            'no_antrian' => $this->no_antrian,

            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toISOString(),

            /*
            |--------------------------------------------------------------------------
            | Data User
            |--------------------------------------------------------------------------
            */

            'user' => $this->whenLoaded(
                'user',
                fn () => [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ]
            ),

            /*
            |--------------------------------------------------------------------------
            | Dokumen
            |--------------------------------------------------------------------------
            */

            'dokumen' => $this->whenLoaded(
                'dokumen',
                fn () => $this->dokumen->map(fn ($dokumen) => [
                    'id' => $dokumen->id,
                    'jenis_dokumen' => $dokumen->jenis_dokumen,
                    'nama_file' => $dokumen->nama_file,
                    'url' => route('admin.files.sku', [
                        'dokumen' => $dokumen->id,
                    ]),
                ])
            ),

            /*
            |--------------------------------------------------------------------------
            | Riwayat
            |--------------------------------------------------------------------------
            */

            'riwayat' => $this->whenLoaded(
                'riwayat',
                fn () => $this->riwayat->map(fn ($riwayat) => [
                    'id' => $riwayat->id,
                    'status' => $riwayat->status,
                    'catatan' => $riwayat->catatan,
                    'changed_by' => $riwayat->changed_by,
                    'created_at' => $riwayat->created_at?->toISOString(),
                ])
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}