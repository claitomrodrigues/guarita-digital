<?php

namespace Database\Seeders;

use App\Enums\SentidoPontoAcesso;
use App\Models\PontoAcesso;
use Illuminate\Database\Seeder;

class PontoAcessoSeeder extends Seeder
{
    public function run(): void
    {
        PontoAcesso::query()->updateOrCreate(
            ['codigo' => 'GUARITA-PRINCIPAL'],
            [
                'nome' => 'Guarita principal',
                'sentido' => SentidoPontoAcesso::Ambos,
                'localizacao' => 'Entrada principal do campus',
                'descricao' => 'Ponto padrão para registros de entrada e saída.',
                'ativo' => true,
            ],
        );
    }
}
