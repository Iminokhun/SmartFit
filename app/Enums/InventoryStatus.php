<?php


namespace App\Enums;

enum InventoryStatus: int
{
    case Available  = 1;
    case Repair     = 2;
    case WrittenOff = 3;

    public function label(): string
    {
        return match ($this) {
            self::Available  => 'Available',
            self::Repair     => 'Repair',
            self::WrittenOff => 'Written off',
        };
    }


    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
