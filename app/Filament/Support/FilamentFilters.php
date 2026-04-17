<?php

namespace App\Filament\Support;

use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class FilamentFilters
{
    /**
     * Standard from/until date-range filter for a single column.
     */
    public static function dateRange(string $column = 'created_at', string $label = 'Date range'): Filter
    {
        return Filter::make($column)
            ->label($label)
            ->form([
                DatePicker::make('from')->label('From'),
                DatePicker::make('until')->label('Until'),
            ])
            ->query(function (Builder $query, array $data) use ($column): void {
                if (! empty($data['from'])) {
                    $query->whereDate($column, '>=', $data['from']);
                }

                if (! empty($data['until'])) {
                    $query->whereDate($column, '<=', $data['until']);
                }
            });
    }
}
