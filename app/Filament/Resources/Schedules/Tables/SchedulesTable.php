<?php

namespace App\Filament\Resources\Schedules\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Actions\Action;
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

                TextColumn::make('hall.name')
                    ->label('Hall')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_time'),
                TextColumn::make('end_time'),

                TextColumn::make('max_participants')
                    ->label('Participants')
            ])
            ->filters([
                SelectFilter::make('day')
                    ->label('Day')
                    ->multiple()
                    ->options([
                        'monday' => 'Monday',
                        'tuesday' => 'Tuesday',
                        'wednesday' => 'Wednesday',
                        'thursday' => 'Thursday',
                        'friday' => 'Friday',
                        'saturday' => 'Saturday',
                    ])
                    ->query(function ($query, array $state) {
                        $days = $state['values'] ?? [];
                        if (empty($days)) {
                            return;
                        }

                        $query->where(function ($subQuery) use ($days) {
                            foreach ($days as $day) {
                                $subQuery->orWhereJsonContains('days_of_week', $day);
                            }
                        });
                    }),

                SelectFilter::make('trainer_id')
                    ->label('Trainer')
                    ->relationship('staff', 'full_name')
                    ->multiple()
                    ->searchable()
                    ->preload(),

                SelectFilter::make('hall_id')
                    ->label('Hall')
                    ->relationship('hall', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),

                SelectFilter::make('activity_id')
                    ->label('Activity')
                    ->relationship('activity', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
                \Filament\Actions\Action::make('attendance')
                    ->label('Attendance')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->url(fn ($record) => \App\Filament\Resources\Schedules\ScheduleResource::getUrl('attendance', ['record' => $record]))
                    ->color('success'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
