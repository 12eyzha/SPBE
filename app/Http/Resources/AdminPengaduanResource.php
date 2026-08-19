<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminPengaduanResource extends JsonResource
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
            | Identitas Pelapor
            |--------------------------------------------------------------------------
            */

            'nama' => $this->nama,
            'nomor' => $this->nomor,

            /*
            |--------------------------------------------------------------------------
            | Informasi Pengaduan
            |--------------------------------------------------------------------------
            */

            'subjek' => $this->subjek,
            'keterangan' => $this->keterangan,
            'lokasi' => $this->lokasi,
            'rt' => $this->rt,
            'rw' => $this->rw,

            /*
            |--------------------------------------------------------------------------
            | Foto Bukti
            |--------------------------------------------------------------------------
            */

            'foto_bukti' => $this->when(
                $this->foto_bukti !== null,
                fn () => [
                    'tersedia' => true,
                    'url' => route('admin.files.pengaduan.foto', [
                        'pengaduan' => $this->id,
                    ]),
                ]
            ),

            /*
            |--------------------------------------------------------------------------
            | Dokumen Pendukung
            |--------------------------------------------------------------------------
            */

            'dokumen' => $this->whenLoaded(
                'dokumen',
                fn () => $this->dokumen->map(fn ($dokumen) => [
                    'id' => $dokumen->id,
                    'nama_file' => $dokumen->nama_file,
                    'url' => route('admin.files.pengaduan', [
                        'dokumen' => $dokumen->id,
                    ]),
                ])
            ),

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'status' => $this->status,

            /*
            |--------------------------------------------------------------------------
            | Respon Petugas
            |--------------------------------------------------------------------------
            */

            'respon' => $this->whenLoaded(
                'respon',
                fn () => $this->respon->map(fn ($respon) => [
                    'id' => $respon->id,
                    'respon' => $respon->respon,
                    'created_at' => $respon->created_at?->toISOString(),
                ])
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}