<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::select('SELECT 1');
            $banco = 'online';
            $status = 200;
        } catch (Throwable) {
            $banco = 'indisponivel';
            $status = 503;
        }

        return response()->json([
            'sistema' => 'Guarita Digital',
            'status' => $status === 200 ? 'online' : 'degradado',
            'banco' => $banco,
            'timestamp' => now()->toIso8601String(),
        ], $status);
    }
}
