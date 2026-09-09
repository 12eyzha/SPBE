<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PengajuanKtpResource extends JsonResource
{
    /**
     * Transform resource into an array.
     */
    public function toArray(
        Request $request
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Status Expired
        |--------------------------------------------------------------------------
        |
        | Hanya pengajuan yang sudah disetujui
        | dan memiliki expired_at yang bisa expired.
        |
        */

        $isExpired = false;

        if (
            $this->status === 'disetujui' &&
            $this->expired_at !== null
        ) {
            $isExpired = Carbon::parse(
                (string) $this->expired_at
            )->isPast();
        }

        /*
        |--------------------------------------------------------------------------
        | Visit Date
        |--------------------------------------------------------------------------
        */

        $visitDate = null;

        if ($this->visit_date !== null) {
            $visitDate = Carbon::parse(
                (string) $this->visit_date
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Expired At
        |--------------------------------------------------------------------------
        */

        $expiredAt = null;

        if ($this->expired_at !== null) {
            $expiredAt = Carbon::parse(
                (string) $this->expired_at
            );
        }

        return [
            /*
            |--------------------------------------------------------------------------
            | Identitas Pengajuan
            |--------------------------------------------------------------------------
            */

            'id' =>
                $this->id,

            'jenis_permohonan' =>
                $this->jenis_permohonan,

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
                    ?->format('Y-m-d'),

            'jenis_kelamin' =>
                $this->jenis_kelamin,

            /*
            |--------------------------------------------------------------------------
            | Alamat
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
            | Keperluan
            |--------------------------------------------------------------------------
            */

            'keperluan' =>
                $this->keperluan,

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
            | Approval
            |--------------------------------------------------------------------------
            */

            'no_antrian' =>
                $this->no_antrian,

            'approved_at' =>
                $this->approved_at
                    ?->toISOString(),

            /*
            |--------------------------------------------------------------------------
            | Jadwal Kunjungan
            |--------------------------------------------------------------------------
            */

            'visit_date' =>
                $visitDate
                    ?->format('Y-m-d'),

            'visit_date_label' =>
                $visitDate
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
            | Expired
            |--------------------------------------------------------------------------
            */

            'expired_at' =>
                $expiredAt
                    ?->toISOString(),

            'expired_date' =>
                $expiredAt
                    ?->translatedFormat(
                        'd F Y'
                    ),

            'expired_time' =>
                $expiredAt
                    ?->format('H:i'),

            'expired_datetime_label' =>
                $expiredAt
                    ?->translatedFormat(
                        'd F Y H:i'
                    ),

            'is_expired' =>
                $isExpired,

            'eligibility' =>
                $this->status === 'disetujui'
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
                                        'files.ktp',
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
            | Timestamp
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

        if (
            strlen($nik) < 8
        ) {
            return $nik;
        }

        return substr(
            $nik,
            0,
            4
        )
            . str_repeat(
                '*',
                max(
                    0,
                    strlen($nik) - 8
                )
            )
            . substr(
                $nik,
                -4
            );
    }

    /**
     * Masking Nomor KK untuk user.
     *
     * Contoh:
     * 1234567890123456
     * menjadi:
     * 1234********3456
     */
    private function maskNomorKk(
        ?string $nomorKk
    ): ?string {
        if (! $nomorKk) {
            return null;
        }

        if (
            strlen($nomorKk) < 8
        ) {
            return $nomorKk;
        }

        return substr(
            $nomorKk,
            0,
            4
        )
            . str_repeat(
                '*',
                max(
                    0,
                    strlen($nomorKk) - 8
                )
            )
            . substr(
                $nomorKk,
                -4
            );
    }
}