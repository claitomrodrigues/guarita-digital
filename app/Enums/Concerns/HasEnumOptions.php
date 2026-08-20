<?php

namespace App\Enums\Concerns;

trait HasEnumOptions
{
    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(
            static fn (self $item): string => $item->value,
            self::cases(),
        );
    }

    /** @return list<array{value: string, label: string}> */
    public static function opcoes(): array
    {
        return array_map(
            static fn (self $item): array => [
                'value' => $item->value,
                'label' => $item->label(),
            ],
            self::cases(),
        );
    }
}
