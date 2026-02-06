<?php

namespace App\Filament\Resources\Schedules\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('activity.name')
                    ->label('Activity')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('staff.full_name')
                    ->label('Trainer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('days_of_week')
                    ->label('Days')
                    ->listWithLineBreaks()
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state)),

                TextColumn::make('hall'),

                TextColumn::make('start_time'),
                TextColumn::make('end_time'),

                TextColumn::make('max_participants')
                    ->label('Participants')
            ])
            ->filters([
//                SelectFilter::make('day')
//                    ->label('Day')
//                    ->options([
//                        'monday' => 'Monday',
//                        'tuesday' => 'Tuesday',
//                        'wednesday' => 'Wednesday',
//                        'thursday' => 'Thursday',
//                        'friday' => 'Friday',
//                        'saturday' => 'Saturday',
//                    ])
//                    ->query(function ($query, $state) {
//                        if (!$state) return;
//
//                        $query->whereJsonContains('days_of_week', $state);
//                    }),
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
