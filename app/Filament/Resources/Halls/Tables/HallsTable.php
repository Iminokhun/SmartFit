<?php

namespace App\Filament\Resources\Halls\Tables;

use App\Filament\Resources\Halls\HallResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HallsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn ($record) => HallResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('schedules_count')
                    ->label('Activities')
                    ->counts('schedules')
                    ->sortable(),

                TextColumn::make('description')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn ($record) => auth()->user()?->can('delete', $record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('deleteAny', \App\Models\Hall::class)),
                ]),
            ]);
    }
}
