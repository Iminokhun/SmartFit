<?php

namespace App\Filament\Resources\Schedules\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
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

                TextColumn::make('day_of_week')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state)),

                TextColumn::make('hall'),

                TextColumn::make('start_time'),
                TextColumn::make('end_time'),

                TextColumn::make('max_participants')
                    ->label('Max participants')
            ])
            ->filters([
                //
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
