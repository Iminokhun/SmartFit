<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash   = 'cash';
    case Card   = 'card';
    case Online = 'online';

    public function label(): string
    {
        return match ($this) {
            self::Cash   => 'Cash',
            self::Card   => 'Card',
            self::Online => 'Online',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
