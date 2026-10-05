<?php

namespace App\Http\Middleware;

use App\Models\PontoAcesso;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateCamera
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?: $request->header('X-Camera-Token');

        if (! is_string($token) || strlen($token) < 32) {
            return $this->negado();
        }

        $ponto = PontoAcesso::query()
            ->where('camera_token_hash', hash('sha256', $token))
            ->where('ativo', true)
            ->first();

        if ($ponto === null || $ponto->trashed()) {
            return $this->negado();
        }

        $request->attributes->set('camera_ponto_acesso', $ponto);

        return $next($request);
    }

    private function negado(): JsonResponse
    {
        return response()->json(['message' => 'Token da câmera inválido ou inativo.'], 401);
    }
}
