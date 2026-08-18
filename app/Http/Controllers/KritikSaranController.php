<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKritikSaranRequest;
use App\Models\KritikSaran;
use Illuminate\Http\JsonResponse;
use Throwable;

class KritikSaranController extends Controller
{
    /**
     * Menyimpan kritik atau saran baru.
     */
    public function store(StoreKritikSaranRequest $request): JsonResponse
    {
        try {
            $kritikSaran = KritikSaran::create([
                'nama' => $request->nama,
                'email' => $request->email,
                'pesan' => $request->pesan,
            ]);

            return response()->json([
                'message' => 'Kritik dan saran berhasil dikirim.',
                'data' => [
                    'id' => $kritikSaran->id,
                    'nama' => $kritikSaran->nama,
                    'email' => $kritikSaran->email,
                    'pesan' => $kritikSaran->pesan,
                    'created_at' => $kritikSaran->created_at?->toISOString(),
                ],
            ], 201);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Kritik dan saran gagal dikirim.',
            ], 500);
        }
    }
}