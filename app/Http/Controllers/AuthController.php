<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(LoginRequest $request): RedirectResponse
    {
        $login = (string) $request->validated('login');
        $campo = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'matricula';

        if (! Auth::attempt([
            $campo => $login,
            'password' => $request->validated('password'),
        ], $request->boolean('remember'))) {
            return back()
                ->withErrors(['login' => 'Matrícula/e-mail ou senha inválidos.'])
                ->withInput($request->only('login'));
        }

        $request->session()->regenerate();
        $usuario = $request->user();

        if (! $usuario || ! $usuario->ativo || $usuario->trashed()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors(['login' => 'Este usuário está inativo.'])
                ->withInput($request->only('login'));
        }

        $usuario->forceFill(['ultimo_login_em' => now()])->saveQuietly();

        return redirect()->intended(route('home'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
