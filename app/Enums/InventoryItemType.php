<?php

namespace App\Enums;

enum InventoryItemType: string
{
    case Asset = 'asset';
    case Consumable = 'consumable';
    case Retail = 'retail';

    public function label(): string
    {
        return match ($this) {
            self::Asset => 'Asset',
            self::Consumable => 'Consumable',
            self::Retail => 'Retail',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
