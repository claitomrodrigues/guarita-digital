<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $chave = $this->chaveLimitador($request);
        $maximoTentativas = max(3, (int) config('guarita.login_max_attempts', 5));

        if (RateLimiter::tooManyAttempts($chave, $maximoTentativas)) {
            $segundos = RateLimiter::availableIn($chave);

            throw ValidationException::withMessages([
                'email' => ["Muitas tentativas de acesso. Tente novamente em {$segundos} segundos."],
            ]);
        }

        $credenciais = $request->safe()->only(['email', 'password']);

        if (! Auth::attempt($credenciais, $request->boolean('remember'))) {
            RateLimiter::hit($chave, max(30, (int) config('guarita.login_decay_seconds', 60)));

            throw ValidationException::withMessages([
                'email' => ['E-mail ou senha inválidos.'],
            ]);
        }

        $request->session()->regenerate();

        /** @var User $usuario */
        $usuario = $request->user();

        if (! $usuario->ativo || $usuario->trashed()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'message' => 'Este usuário está inativo.',
            ], 403);
        }

        RateLimiter::clear($chave);
        $usuario->forceFill(['ultimo_login_em' => now()])->saveQuietly();

        return response()->json([
            'message' => 'Login realizado com sucesso.',
            'data' => new UserResource($usuario),
        ]);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Sessão encerrada com sucesso.',
        ]);
    }

    private function chaveLimitador(LoginRequest $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip());
    }
}
