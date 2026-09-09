<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PengajuanSkuResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(
        Request $request
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Expired
        |--------------------------------------------------------------------------
        |
        | Pengajuan hanya dianggap expired apabila:
        | - status disetujui
        | - expired_at tersedia
        | - expired_at sudah lewat
        |
        */

        $isExpired =
            $this->status ===
                'disetujui' &&
            $this->expired_at !== null &&
            $this->expired_at->isPast();

        return [
            'id' =>
                $this->id,

            /*
            |--------------------------------------------------------------------------
            | Data Pemohon
            |--------------------------------------------------------------------------
            */

            'nik' =>
                $this->maskNik(
                    $this->nik
                ),

            'nama_lengkap' =>
                $this->nama_lengkap,

            'nomor_kk' =>
                $this->maskNomorKk(
                    $this->nomor_kk
                ),

            'tempat_lahir' =>
                $this->tempat_lahir,

            'tanggal_lahir' =>
                $this->tanggal_lahir
                    ?->format(
                        'Y-m-d'
                    ),

            'jenis_kelamin' =>
                $this->jenis_kelamin,

            /*
            |--------------------------------------------------------------------------
            | Alamat Pemohon
            |--------------------------------------------------------------------------
            */

            'alamat' =>
                $this->alamat,

            'rt' =>
                $this->rt,

            'rw' =>
                $this->rw,

            'kode_pos' =>
                $this->kode_pos,

            /*
            |--------------------------------------------------------------------------
            | Data Usaha
            |--------------------------------------------------------------------------
            */

            'nama_usaha' =>
                $this->nama_usaha,

            'jenis_usaha' =>
                $this->jenis_usaha,

            'deskripsi_usaha' =>
                $this->deskripsi_usaha,

            'alamat_usaha' =>
                $this->alamat_usaha,

            'rt_usaha' =>
                $this->rt_usaha,

            'rw_usaha' =>
                $this->rw_usaha,

            'lama_menjalankan_usaha' =>
                $this->lama_menjalankan_usaha,

            'perkiraan_penghasilan_per_bulan' =>
                $this->perkiraan_penghasilan_per_bulan,

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'status' =>
                $this->status,

            'catatan_admin' =>
                $this->catatan_admin,

            /*
            |--------------------------------------------------------------------------
            | Antrean
            |--------------------------------------------------------------------------
            */

            'no_antrian' =>
                $this->no_antrian,

            /*
            |--------------------------------------------------------------------------
            | Approval
            |--------------------------------------------------------------------------
            */

            'approved_at' =>
                $this->approved_at
                    ?->toISOString(),

            /*
            |--------------------------------------------------------------------------
            | Jadwal Kunjungan
            |--------------------------------------------------------------------------
            */

            'visit_date' =>
                $this->visit_date
                    ?->format(
                        'Y-m-d'
                    ),

            'visit_date_label' =>
                $this->visit_date
                    ?->translatedFormat(
                        'l, d F Y'
                    ),

            /*
            |--------------------------------------------------------------------------
            | Jam Pelayanan
            |--------------------------------------------------------------------------
            */

            'service_hours' => [
                'start' =>
                    '08:30',

                'end' =>
                    '13:00',

                'label' =>
                    '08.30–13.00 WIB',
            ],

            /*
            |--------------------------------------------------------------------------
            | Masa Berlaku
            |--------------------------------------------------------------------------
            */

            'expired_at' =>
                $this->expired_at
                    ?->toISOString(),

            'expired_date' =>
                $this->expired_at
                    ?->translatedFormat(
                        'd F Y'
                    ),

            'is_expired' =>
                $isExpired,

            'eligibility' =>
                $this->status ===
                'disetujui'
                    ? (
                        $isExpired
                            ? 'expired'
                            : 'active'
                    )
                    : null,

            /*
            |--------------------------------------------------------------------------
            | Dokumen
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

                                'jenis_dokumen' =>
                                    $dokumen->jenis_dokumen,

                                'nama_file' =>
                                    $dokumen->nama_file,

                                'url' =>
                                    route(
                                        'files.sku',
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
            | Riwayat
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
     * Masking NIK untuk user.
     *
     * Contoh:
     * 1234567890123456
     * menjadi:
     * 1234********3456
     */
    private function maskNik(
        ?string $nik
    ): ?string {
        if (! $nik) {
            return null;
        }

        return substr(
            $nik,
            0,
            4
        )
            . str_repeat(
                '*',
                8
            )
            . substr(
                $nik,
                -4
            );
    }

    /**
     * Masking Nomor KK untuk user.
     */
    private function maskNomorKk(
        ?string $nomorKk
    ): ?string {
        if (! $nomorKk) {
            return null;
        }

        return substr(
            $nomorKk,
            0,
            4
        )
            . str_repeat(
                '*',
                8
            )
            . substr(
                $nomorKk,
                -4
            );
    }
}