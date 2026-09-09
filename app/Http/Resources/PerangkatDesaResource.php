<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerangkatDesaResource extends JsonResource
{
    /**
     * Transform resource into array.
     */
    public function toArray(
        Request $request
    ): array {
        return [
            'id' => $this->id,

            'nama' =>
                $this->nama,

            'jabatan' =>
                $this->jabatan,

            /*
            |--------------------------------------------------------------------------
            | FOTO
            |--------------------------------------------------------------------------
            |
            | Jangan kirim path storage mentah seperti:
            |
            | perangkat-desa/abc.jpg
            |
            | Kirim URL endpoint publik.
            |
            */

            'foto' =>
                filled($this->foto)
                    ? route(
                        'public.files.perangkat-desa',
                        [
                            'perangkatDesa' =>
                                $this->id,
                        ]
                    )
                    : null,

            'urutan' =>
                $this->urutan,

            'is_active' =>
                (bool) $this->is_active,

            'updated_by' =>
                $this->whenLoaded(
                    'updatedBy',
                    function () {
                        return [
                            'id' =>
                                $this->updatedBy?->id,

                            'name' =>
                                $this->updatedBy?->name,
                        ];
                    }
                ),

            'created_at' =>
                $this->created_at,

            'updated_at' =>
                $this->updated_at,
        ];
    }
}