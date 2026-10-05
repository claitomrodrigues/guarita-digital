<?php

namespace Database\Seeders;

use App\Enums\SentidoPontoAcesso;
use App\Models\PontoAcesso;
use Illuminate\Database\Seeder;

class PontoAcessoSeeder extends Seeder
{
    public function run(): void
    {
        $pontos = [
            ['codigo' => 'GUARITA-ENTRADA', 'nome' => 'Entrada principal', 'sentido' => SentidoPontoAcesso::Entrada],
            ['codigo' => 'GUARITA-SAIDA', 'nome' => 'Saída principal', 'sentido' => SentidoPontoAcesso::Saida],
        ];

        foreach ($pontos as $ponto) {
            PontoAcesso::query()->updateOrCreate(
                ['codigo' => $ponto['codigo']],
                [
                    ...$ponto,
                    'localizacao' => 'Guarita principal do campus',
                    'descricao' => 'Ponto padrão de controle do fluxo veicular.',
                    'ativo' => true,
                ],
            );
        }
    }
}
