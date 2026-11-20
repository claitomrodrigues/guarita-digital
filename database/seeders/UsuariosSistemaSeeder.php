<?php

namespace Database\Seeders;

use App\Enums\PerfilUsuario;
use App\Models\User;
use Illuminate\Database\Seeder;
use InvalidArgumentException;

class UsuariosSistemaSeeder extends Seeder
{
    public function run(): void
    {
        $configuracao = config('guarita.admin');
        $email = mb_strtolower(trim((string) $configuracao['email']), 'UTF-8');
        $senha = (string) $configuracao['password'];

        if (app()->environment('production') && strlen($senha) < 16) {
            throw new InvalidArgumentException(
                'Configure ADMIN_PASSWORD com pelo menos 16 caracteres antes de executar o seeder em produção.',
            );
        }

        User::withTrashed()
            ->where('email', '!=', $email)
            ->update([
                'ativo' => false,
                'deleted_at' => now(),
            ]);

        $usuario = User::withTrashed()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $configuracao['name'],
                'matricula' => $configuracao['matricula'],
                'perfil' => PerfilUsuario::Seguranca,
                'password' => $senha,
                'ativo' => true,
            ],
        );

        $usuario->forceFill([
            'email_verified_at' => now(),
            'deleted_at' => null,
        ])->save();
    }
}
