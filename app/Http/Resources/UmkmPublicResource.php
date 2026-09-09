<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UmkmPublicResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' =>
                $this->id,

            /*
            |--------------------------------------------------------------------------
            | Data UMKM
            |--------------------------------------------------------------------------
            */

            'nama_umkm' =>
                $this->nama_umkm,

            'kategori' =>
                $this->whenLoaded(
                    'kategori',
                    fn () => [
                        'id' =>
                            $this->kategori->id,

                        'nama' =>
                            $this->kategori->nama,
                    ]
                ),

            'deskripsi_umkm' =>
                $this->deskripsi_umkm,

            /*
            |--------------------------------------------------------------------------
            | Harga
            |--------------------------------------------------------------------------
            */

            'harga_min' =>
                $this->harga_min,

            'harga_max' =>
                $this->harga_max,

            /*
            |--------------------------------------------------------------------------
            | Lokasi
            |--------------------------------------------------------------------------
            */

            'alamat' =>
                $this->alamat,

            /*
            |--------------------------------------------------------------------------
            | Jam Operasional
            |--------------------------------------------------------------------------
            */

            'jam_buka_mulai' =>
                $this->jam_buka_mulai
                    ?->format('H:i'),

            'jam_buka_selesai' =>
                $this->jam_buka_selesai
                    ?->format('H:i'),

            /*
            |--------------------------------------------------------------------------
            | Kontak
            |--------------------------------------------------------------------------
            */

            'nomor_wa' =>
                $this->nomor_wa,

            'link_ecommerce' =>
                $this->link_ecommerce,

            /*
            |--------------------------------------------------------------------------
            | Status Public
            |--------------------------------------------------------------------------
            */

            'status' =>
                $this->status,

            'is_active' =>
                $this->is_active,

            /*
            |--------------------------------------------------------------------------
            | Foto
            |--------------------------------------------------------------------------
            */

            'foto' =>
                $this->whenLoaded(
                    'foto',
                    fn () =>
                        $this->foto->map(
                            fn ($foto) => [
                                'id' =>
                                    $foto->id,

                                'urutan' =>
                                    $foto->urutan,

                                'url' =>
                                    route(
                                        'public.files.umkm',
                                        [
                                            'foto' =>
                                                $foto->id,
                                        ]
                                    ),
                            ]
                        )->values()
                ),

            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */

            'created_at' =>
                $this->created_at
                    ?->toISOString(),

            'updated_at' =>
                $this->updated_at
                    ?->toISOString(),
        ];
    }
}