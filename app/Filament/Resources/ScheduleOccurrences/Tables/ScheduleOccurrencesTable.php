<?php

namespace App\Filament\Resources\ScheduleOccurrences\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ScheduleOccurrencesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('schedule.activity.name')
                    ->label('Activity')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('schedule.staff.full_name')
                    ->label('Trainer')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('start_time')
                    ->label('Start')
                    ->sortable(),

                TextColumn::make('end_time')
                    ->label('End')
                    ->sortable(),

                TextColumn::make('max_participants')
                    ->label('Max'),

                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'planned',
                        'gray' => 'completed',
                        'danger' => 'cancelled',
                    ]),
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

