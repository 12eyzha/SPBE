<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PengajuanUmkmResource extends JsonResource
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
            | Data UMKM
            |--------------------------------------------------------------------------
            */

            'nama_umkm' => $this->nama_umkm,

            'kategori' => $this->whenLoaded(
                'kategori',
                fn () => [
                    'id' => $this->kategori->id,
                    'nama' => $this->kategori->nama,
                ]
            ),

            'deskripsi_umkm' => $this->deskripsi_umkm,

            'harga_min' => $this->harga_min,
            'harga_max' => $this->harga_max,

            'alamat' => $this->alamat,

            /*
            |--------------------------------------------------------------------------
            | Jam Operasional
            |--------------------------------------------------------------------------
            */

            'jam_buka_mulai' => $this->jam_buka_mulai?->format('H:i'),
            'jam_buka_selesai' => $this->jam_buka_selesai?->format('H:i'),

            /*
            |--------------------------------------------------------------------------
            | Kontak
            |--------------------------------------------------------------------------
            */

            'nomor_wa' => $this->nomor_wa,
            'link_ecommerce' => $this->link_ecommerce,

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'status' => $this->status,
            'is_active' => $this->is_active,
            'catatan_admin' => $this->catatan_admin,
            'no_antrian' => $this->no_antrian,
            'approved_at' => $this->approved_at?->toISOString(),

            /*
            |--------------------------------------------------------------------------
            | Foto
            |--------------------------------------------------------------------------
            */

            'foto' => $this->whenLoaded(
                'foto',
                fn () => $this->foto->map(fn ($foto) => [
                    'id' => $foto->id,
                    'urutan' => $foto->urutan,
                    'url' => route('files.umkm', [
                        'foto' => $foto->id,
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