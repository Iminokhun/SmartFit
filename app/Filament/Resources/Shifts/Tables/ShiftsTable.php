<?php

namespace App\Filament\Resources\Shifts\Tables;

use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Shifts\ShiftResource;
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
            ->recordUrl(fn ($record) => ShiftResource::getUrl('view', ['record' => $record]))
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
                    ->multiple()
                    ->relationship('staff', 'full_name')
                    ->searchable()
                    ->preload(),

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
                        'sunday' => 'Sunday',
                    ])
                    ->query(function ($query, array $data) {
                        $day = $data['value'] ?? null;

                        if (blank($day)) {
                            return $query;
                        }

                        return $query->whereJsonContains('days_of_week', $day);
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn ($record) => auth()->user()?->can('delete', $record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('deleteAny', \App\Models\Shift::class)),
                ]),
            ]);
    }
}
