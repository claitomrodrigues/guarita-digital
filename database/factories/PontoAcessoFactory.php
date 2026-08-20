<?php

namespace Database\Factories;

use App\Enums\SentidoPontoAcesso;
use App\Models\PontoAcesso;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PontoAcesso> */
class PontoAcessoFactory extends Factory
{
    protected $model = PontoAcesso::class;

    public function definition(): array
    {
        return [
            'nome' => 'Guarita '.fake()->unique()->numberBetween(1, 999),
            'codigo' => strtoupper(fake()->unique()->bothify('GUARITA-###')),
            'sentido' => SentidoPontoAcesso::Ambos,
            'localizacao' => fake()->streetName(),
            'descricao' => null,
            'ativo' => true,
        ];
    }
}
