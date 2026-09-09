<?php

namespace App\Http\Controllers;

use App\Models\KritikSaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KritikSaranController extends Controller
{
    /**
     * Menyimpan kritik dan saran dari masyarakat.
     *
     * POST /api/kritik-saran
     */
    public function store(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'nama' => [
                'required',
                'string',
                'max:150',
            ],

            'email' => [
                'required',
                'email',
                'max:150',
            ],

            'pesan' => [
                'required',
                'string',
                'max:5000',
            ],
        ]);

        $kritikSaran = KritikSaran::create([
            'nama' => $validated['nama'],
            'email' => $validated['email'],
            'pesan' => $validated['pesan'],
        ]);

        return response()->json([
            'message' =>
                'Kritik dan saran berhasil dikirim. Terima kasih atas masukan Anda.',

            'data' =>
                $kritikSaran,
        ], 201);
    }
}