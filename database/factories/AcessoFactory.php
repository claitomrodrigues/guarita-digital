<?php

namespace Database\Factories;

use App\Enums\OrigemAcesso;
use App\Enums\StatusAcesso;
use App\Enums\TipoAcesso;
use App\Models\Acesso;
use App\Models\PontoAcesso;
use App\Models\Veiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Acesso> */
class AcessoFactory extends Factory
{
    protected $model = Acesso::class;

    public function definition(): array
    {
        return [
            'veiculo_id' => null,
            'pessoa_id' => null,
            'user_id' => null,
            'ponto_acesso_id' => PontoAcesso::factory(),
            'placa_reconhecida' => strtoupper(fake()->lexify('???')).fake()->randomDigitNotNull().strtoupper(fake()->randomLetter()).fake()->numerify('##'),
            'tipo' => TipoAcesso::Entrada,
            'status' => StatusAcesso::NaoCadastrado,
            'origem' => OrigemAcesso::Ocr,
            'data_hora' => now(),
            'imagem' => null,
            'confianca' => null,
            'observacoes' => null,
            'metadata' => null,
        ];
    }

    public function paraVeiculo(Veiculo $veiculo): static
    {
        return $this->state(fn (): array => [
            'veiculo_id' => $veiculo->id,
            'pessoa_id' => $veiculo->pessoa_id,
            'placa_reconhecida' => $veiculo->placa,
            'status' => $veiculo->estaAutorizado()
                ? StatusAcesso::Autorizado
                : StatusAcesso::Bloqueado,
        ]);
    }
}
