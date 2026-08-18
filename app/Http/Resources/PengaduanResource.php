<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PengaduanResource extends JsonResource
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
            'nomor' => $this->maskNomor($this->nomor),

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
            | Bukti
            |--------------------------------------------------------------------------
            |
            | Path file tidak dikirim sebagai URL publik.
            | Nanti akses file dilakukan melalui endpoint terproteksi.
            |
            */

            'foto_bukti' => $this->when(
                $this->foto_bukti !== null,
                fn () => [
                    'tersedia' => true,
                ]
            ),

            'dokumen' => $this->whenLoaded(
                'dokumen',
                fn () => $this->dokumen->map(fn ($dokumen) => [
                    'id' => $dokumen->id,
                    'nama_file' => $dokumen->nama_file,
                    'tersedia' => true,
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
            | Respon Pengaduan
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

    private function maskNomor(?string $nomor): ?string
    {
        if (! $nomor) {
            return null;
        }

        $length = strlen($nomor);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return substr($nomor, 0, 2)
            . str_repeat('*', $length - 4)
            . substr($nomor, -2);
    }
}