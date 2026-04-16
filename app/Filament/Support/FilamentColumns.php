<?php

namespace App\Filament\Support;

use Filament\Tables\Columns\TextColumn;

class FilamentColumns
{
    /**
     * Badge column with ucfirst formatting and a state→color map.
     *
     * @param array<string, string> $colorMap  ['state_value' => 'filament_color']
     */
    public static function statusBadge(string $name, array $colorMap, ?string $label = null): TextColumn
    {
        $col = TextColumn::make($name)
            ->badge()
            ->color(fn ($state) => $colorMap[(string) $state] ?? 'gray')
            ->formatStateUsing(fn ($state) => ucfirst((string) $state));

        if ($label !== null) {
            $col->label($label);
        }

        return $col;
    }

    /**
     * Sortable money column (defaults to UZS).
     */
    public static function money(string $name, ?string $label = null, string $currency = 'UZS'): TextColumn
    {
        $col = TextColumn::make($name)
            ->money($currency)
            ->sortable();

        if ($label !== null) {
            $col->label($label);
        }

        return $col;
    }
}
