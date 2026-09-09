<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GaleriResource extends JsonResource
{
    /**
     * Transform resource menjadi array.
     */
    public function toArray(
        Request $request
    ): array {
        return [
            'id' =>
                $this->id,

            /*
            |--------------------------------------------------------------------------
            | URL foto publik
            |--------------------------------------------------------------------------
            |
            | File tetap berada di private/local storage.
            | Frontend tidak membaca file_path secara langsung.
            |
            */

            'foto' =>
                url(
                    '/api/files/public/galeri/' .
                    $this->id
                ),

            'file_path' =>
                $this->file_path,

            /*
            |--------------------------------------------------------------------------
            | CREATED BY
            |--------------------------------------------------------------------------
            */

            'created_by' =>
                $this->whenLoaded(
                    'createdBy',
                    function () {
                        return $this->createdBy
                            ? [
                                'id' =>
                                    $this->createdBy->id,

                                'name' =>
                                    $this->createdBy->name,
                            ]
                            : null;
                    }
                ),

            /*
            |--------------------------------------------------------------------------
            | UPDATED BY
            |--------------------------------------------------------------------------
            */

            'updated_by' =>
                $this->whenLoaded(
                    'updatedBy',
                    function () {
                        return $this->updatedBy
                            ? [
                                'id' =>
                                    $this->updatedBy->id,

                                'name' =>
                                    $this->updatedBy->name,
                            ]
                            : null;
                    }
                ),

            'created_at' =>
                $this->created_at,

            'updated_at' =>
                $this->updated_at,
        ];
    }
}