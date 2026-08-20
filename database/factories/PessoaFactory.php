<?php

namespace Database\Factories;

use App\Enums\TipoVinculo;
use App\Models\Pessoa;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Pessoa> */
class PessoaFactory extends Factory
{
    protected $model = Pessoa::class;

    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'cpf' => null,
            'matricula' => fake()->optional()->unique()->numerify('########'),
            'email' => fake()->optional()->safeEmail(),
            'telefone' => fake()->optional()->phoneNumber(),
            'tipo_vinculo' => fake()->randomElement(TipoVinculo::cases()),
            'ativo' => true,
            'observacoes' => null,
        ];
    }
}
