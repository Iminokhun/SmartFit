<?php

namespace App\Filament\Resources\Shifts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ShiftsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('staff.full_name')
                    ->label('Trainer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('days_of_week')
                    ->label('Days')
                    ->formatStateUsing(fn ($state) => collect((array) $state)
                        ->map(fn ($day) => ucfirst((string) $day))
                        ->implode(', '))
                    ->wrap(),

                TextColumn::make('start_time')
                    ->label('From')
                    ->time('H:i')
                    ->sortable(),

                TextColumn::make('end_time')
                    ->label('To')
                    ->time('H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('staff_id')
                    ->label('Trainer')
                    ->relationship('staff', 'full_name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('day')
                    ->label('Day')
                    ->options([
                        'monday' => 'Monday',
                        'tuesday' => 'Tuesday',
                        'wednesday' => 'Wednesday',
                        'thursday' => 'Thursday',
                        'friday' => 'Friday',
                        'saturday' => 'Saturday',
                        'sunday' => 'Sunday',
                    ])
                    ->query(function ($query, $state) {
                        if (! $state) {
                            return $query;
                        }

                        return $query->whereJsonContains('days_of_week', $state);
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

