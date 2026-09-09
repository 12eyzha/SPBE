<?php

namespace App\Http\Controllers;

use App\Models\Sambutan;
use Illuminate\Http\JsonResponse;

class SambutanController extends Controller
{
    /**
     * Mengambil data sambutan yang sedang aktif.
     *
     * Karena desain Sambutan dibatasi maksimal 1 data aktif,
     * kita cukup mengambil data pertama.
     */
    public function index(): JsonResponse
    {
        $sambutan = Sambutan::query()
            ->with([
                'createdBy:id,name',
                'updatedBy:id,name',
            ])
            ->first();

        return response()->json([
            'sambutan' => $sambutan,
        ]);
    }
}