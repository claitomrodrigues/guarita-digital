<?php

namespace App\Http\Middleware;

use App\Enums\PerfilUsuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasProfile
{
    public function handle(Request $request, Closure $next, string ...$perfis): Response
    {
        $usuario = $request->user();
        $perfisValidos = array_values(array_filter(
            $perfis,
            static fn (string $perfil): bool => PerfilUsuario::tryFrom($perfil) !== null,
        ));

        if ($usuario === null || $perfisValidos === [] || ! $usuario->possuiPerfil(...$perfisValidos)) {
            return response()->json([
                'message' => 'Você não possui permissão para executar esta ação.',
            ], 403);
        }

        return $next($request);
    }
}
