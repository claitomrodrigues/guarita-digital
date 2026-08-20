<?php

namespace App\Rules;

use App\Support\Placa;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PlacaBrasileira implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! Placa::valida($value)) {
            $fail('O campo :attribute deve conter uma placa brasileira válida, antiga ou Mercosul.');
        }
    }
}
