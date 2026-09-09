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
                $this->nomor,

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
            | Data User
            |--------------------------------------------------------------------------
            */

            'user' =>
                $this->whenLoaded(
                    'user',
                    fn () => [
                        'id' =>
                            $this->user->id,

                        'name' =>
                            $this->user->name,

                        'email' =>
                            $this->user->email,
                    ]
                ),

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
                                'admin.files.pengaduan.foto',
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

                                'url' =>
                                    route(
                                        'admin.files.pengaduan',
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
            | Respon Petugas
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

                                'user_id' =>
                                    $respon->user_id,

                                'petugas' =>
                                    $respon->relationLoaded(
                                        'user'
                                    )
                                        ? [
                                            'id' =>
                                                $respon
                                                    ->user
                                                    ->id,

                                            'name' =>
                                                $respon
                                                    ->user
                                                    ->name,

                                            'email' =>
                                                $respon
                                                    ->user
                                                    ->email,
                                        ]
                                        : null,

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

                                'changed_by' =>
                                    $riwayat->changed_by,

                                'petugas' =>
                                    $riwayat->relationLoaded(
                                        'changedBy'
                                    )
                                        ? [
                                            'id' =>
                                                $riwayat
                                                    ->changedBy
                                                    ->id,

                                            'name' =>
                                                $riwayat
                                                    ->changedBy
                                                    ->name,

                                            'email' =>
                                                $riwayat
                                                    ->changedBy
                                                    ->email,
                                        ]
                                        : null,

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
}