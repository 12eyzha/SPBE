<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BeritaResource extends JsonResource
{
    /**
     * Transform resource menjadi array JSON.
     */
    public function toArray(
        Request $request
    ): array {
        return [
            'id' =>
                $this->id,

            'judul' =>
                $this->judul,

            'slug' =>
                $this->slug,

            'isi' =>
                $this->isi,

            'status' =>
                $this->status,

            'published_at' =>
                $this->published_at?->toISOString(),

            'created_at' =>
                $this->created_at?->toISOString(),

            'updated_at' =>
                $this->updated_at?->toISOString(),

            /*
            |--------------------------------------------------------------------------
            | Thumbnail Public
            |--------------------------------------------------------------------------
            |
            | Hanya dikirim jika berita sudah published.
            |
            */

            'thumbnail' =>
                $this->when(
                    filled($this->thumbnail) &&
                    $this->status === 'published',
                    fn () =>
                        route(
                            'public.files.berita',
                            [
                                'berita' =>
                                    $this->id,
                            ]
                        )
                ),

            /*
            |--------------------------------------------------------------------------
            | Thumbnail Admin
            |--------------------------------------------------------------------------
            |
            | Admin / Super Admin dapat mengakses thumbnail
            | baik draft maupun published.
            |
            | Frontend admin harus menggunakan field:
            | admin_thumbnail
            |
            */

            'admin_thumbnail' =>
                $this->when(
                    filled($this->thumbnail),
                    fn () =>
                        route(
                            'admin.files.berita',
                            [
                                'berita' =>
                                    $this->id,
                            ]
                        )
                ),

            /*
            |--------------------------------------------------------------------------
            | Creator
            |--------------------------------------------------------------------------
            */

            'created_by' =>
                $this->when(
                    $this->relationLoaded(
                        'createdBy'
                    ),
                    fn () => [
                        'id' =>
                            $this->createdBy?->id,

                        'name' =>
                            $this->createdBy?->name,
                    ]
                ),

            /*
            |--------------------------------------------------------------------------
            | Updater
            |--------------------------------------------------------------------------
            */

            'updated_by' =>
                $this->when(
                    $this->relationLoaded(
                        'updatedBy'
                    ),
                    fn () => [
                        'id' =>
                            $this->updatedBy?->id,

                        'name' =>
                            $this->updatedBy?->name,
                    ]
                ),
        ];
    }
}