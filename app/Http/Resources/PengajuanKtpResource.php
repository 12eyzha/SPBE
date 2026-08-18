<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PengajuanKtpResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'jenis_permohonan' => $this->jenis_permohonan,

            // NIK dimasking untuk response user/public.
            'nik' => $this->maskNik($this->nik),

            'nama_lengkap' => $this->nama_lengkap,
            'nomor_kk' => $this->maskNomorKk($this->nomor_kk),

            'tempat_lahir' => $this->tempat_lahir,
            'tanggal_lahir' => $this->tanggal_lahir?->format('Y-m-d'),
            'jenis_kelamin' => $this->jenis_kelamin,

            'alamat' => $this->alamat,
            'rt' => $this->rt,
            'rw' => $this->rw,
            'kode_pos' => $this->kode_pos,

            'keperluan' => $this->keperluan,

            'status' => $this->status,
            'catatan_admin' => $this->catatan_admin,

            'no_antrian' => $this->no_antrian,

            'approved_at' => $this->approved_at?->toISOString(),

            'dokumen' => $this->whenLoaded(
                'dokumen',
                fn () => $this->dokumen->map(fn ($dokumen) => [
                    'id' => $dokumen->id,
                    'jenis_dokumen' => $dokumen->jenis_dokumen,
                    'file_path' => $dokumen->file_path,
                    'nama_file' => $dokumen->nama_file,
                ])
            ),

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

    private function maskNik(?string $nik): ?string
    {
        if (! $nik) {
            return null;
        }

        return substr($nik, 0, 4)
            . str_repeat('*', 8)
            . substr($nik, -4);
    }

    private function maskNomorKk(?string $nomorKk): ?string
    {
        if (! $nomorKk) {
            return null;
        }

        return substr($nomorKk, 0, 4)
            . str_repeat('*', 8)
            . substr($nomorKk, -4);
    }
}