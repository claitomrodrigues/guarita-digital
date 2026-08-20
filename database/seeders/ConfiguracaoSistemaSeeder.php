<?php

namespace Database\Seeders;

use App\Models\ConfiguracaoSistema;
use Illuminate\Database\Seeder;

class ConfiguracaoSistemaSeeder extends Seeder
{
    public function run(): void
    {
        $itens = [
            [
                'chave' => 'nome_sistema',
                'valor' => 'Guarita Digital',
                'tipo' => 'string',
                'grupo' => 'geral',
                'descricao' => 'Nome exibido pelo sistema.',
                'publica' => true,
            ],
            [
                'chave' => 'instituicao',
                'valor' => 'IFFar - Campus São Vicente do Sul',
                'tipo' => 'string',
                'grupo' => 'geral',
                'descricao' => 'Instituição responsável pelo sistema.',
                'publica' => true,
            ],
            [
                'chave' => 'confirmacoes_ocr',
                'valor' => '2',
                'tipo' => 'integer',
                'grupo' => 'reconhecimento',
                'descricao' => 'Quantidade mínima de leituras iguais para confirmação na interface.',
                'publica' => true,
            ],
            [
                'chave' => 'janela_duplicidade_segundos',
                'valor' => '20',
                'tipo' => 'integer',
                'grupo' => 'acesso',
                'descricao' => 'Intervalo usado para ignorar leituras repetidas da mesma placa.',
                'publica' => false,
            ],
        ];

        foreach ($itens as $item) {
            ConfiguracaoSistema::query()->updateOrCreate(
                ['chave' => $item['chave']],
                $item,
            );
        }
    }
}
