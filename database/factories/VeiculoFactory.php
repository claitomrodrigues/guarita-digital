<?php

namespace Database\Factories;

use App\Enums\TipoVeiculo;
use App\Models\Pessoa;
use App\Models\Veiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Veiculo> */
class VeiculoFactory extends Factory
{
    protected $model = Veiculo::class;

    public function definition(): array
    {
        $letras = strtoupper(fake()->lexify('???'));
        $placa = $letras.fake()->randomDigitNotNull().strtoupper(fake()->randomLetter()).fake()->numerify('##');

        return [
            'pessoa_id' => Pessoa::factory(),
            'placa' => $placa,
            'marca' => fake()->randomElement(['Volkswagen', 'Chevrolet', 'Fiat', 'Toyota', 'Honda']),
            'modelo' => fake()->word(),
            'cor' => fake()->safeColorName(),
            'tipo' => TipoVeiculo::Carro,
            'ano' => fake()->numberBetween(2000, now()->year + 1),
            'ativo' => true,
            'autorizado' => true,
            'observacoes' => null,
        ];
    }
}
