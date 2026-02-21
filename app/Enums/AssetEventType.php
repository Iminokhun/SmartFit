<?php

namespace App\Enums;

enum AssetEventType: string
{
    case Commissioned = 'commissioned';
    case Transferred = 'transferred';
    case SentToRepair = 'sent_to_repair';
    case ReturnedFromRepair = 'returned_from_repair';
    case WrittenOff = 'written_off';

    public function label(): string
    {
        return match ($this) {
            self::Commissioned => 'Commissioned',
            self::Transferred => 'Transferred',
            self::SentToRepair => 'Sent to repair',
            self::ReturnedFromRepair => 'Returned from repair',
            self::WrittenOff => 'Written off',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
