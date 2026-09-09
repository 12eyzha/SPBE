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
            'id' =>
                $this->id,

            /*
            |--------------------------------------------------------------------------
            | Identitas Pelapor
            |--------------------------------------------------------------------------
            */

            'nama' =>
                $this->nama,

            'nomor' =>
                $this->maskNomor(
                    $this->nomor
                ),

            /*
            |--------------------------------------------------------------------------
            | Informasi Pengaduan
            |--------------------------------------------------------------------------
            */

            'subjek' =>
                $this->subjek,

            'keterangan' =>
                $this->keterangan,

            'lokasi' =>
                $this->lokasi,

            'rt' =>
                $this->rt,

            'rw' =>
                $this->rw,

            /*
            |--------------------------------------------------------------------------
            | Foto Bukti
            |--------------------------------------------------------------------------
            */

            'foto_bukti' =>
                $this->when(
                    $this->foto_bukti !== null,
                    fn () => [
                        'tersedia' =>
                            true,

                        'url' =>
                            route(
                                'files.pengaduan.foto',
                                [
                                    'pengaduan' =>
                                        $this->id,
                                ]
                            ),
                    ]
                ),

            /*
            |--------------------------------------------------------------------------
            | Dokumen Pendukung
            |--------------------------------------------------------------------------
            */

            'dokumen' =>
                $this->whenLoaded(
                    'dokumen',
                    fn () =>
                        $this->dokumen->map(
                            fn ($dokumen) => [
                                'id' =>
                                    $dokumen->id,

                                'nama_file' =>
                                    $dokumen->nama_file,

                                'tersedia' =>
                                    true,

                                'url' =>
                                    route(
                                        'files.pengaduan',
                                        [
                                            'dokumen' =>
                                                $dokumen->id,
                                        ]
                                    ),
                            ]
                        )
                ),

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'status' =>
                $this->status,

            /*
            |--------------------------------------------------------------------------
            | Respon Pengaduan
            |--------------------------------------------------------------------------
            */

            'respon' =>
                $this->whenLoaded(
                    'respon',
                    fn () =>
                        $this->respon->map(
                            fn ($respon) => [
                                'id' =>
                                    $respon->id,

                                'respon' =>
                                    $respon->respon,

                                'created_at' =>
                                    $respon
                                        ->created_at
                                        ?->toISOString(),
                            ]
                        )
                ),

            /*
            |--------------------------------------------------------------------------
            | Riwayat Status
            |--------------------------------------------------------------------------
            |
            | User dapat melihat:
            | - status
            | - catatan
            | - waktu perubahan
            |
            | Identitas internal petugas tidak
            | ditampilkan pada resource user.
            |
            */

            'riwayat' =>
                $this->whenLoaded(
                    'riwayat',
                    fn () =>
                        $this->riwayat->map(
                            fn ($riwayat) => [
                                'id' =>
                                    $riwayat->id,

                                'status' =>
                                    $riwayat->status,

                                'catatan' =>
                                    $riwayat->catatan,

                                'created_at' =>
                                    $riwayat
                                        ->created_at
                                        ?->toISOString(),
                            ]
                        )
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

    /**
     * Masking nomor telepon untuk user.
     *
     * Contoh:
     * 081234567890
     * menjadi:
     * 08********90
     */
    private function maskNomor(
        ?string $nomor
    ): ?string {
        if (! $nomor) {
            return null;
        }

        $length =
            strlen($nomor);

        if (
            $length <= 4
        ) {
            return str_repeat(
                '*',
                $length
            );
        }

        return substr(
            $nomor,
            0,
            2
        )
            . str_repeat(
                '*',
                $length - 4
            )
            . substr(
                $nomor,
                -2
            );
    }
}