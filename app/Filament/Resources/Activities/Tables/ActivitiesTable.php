<?php

namespace App\Filament\Resources\Activities\Tables;

use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Support\FilamentActions;
use App\Models\Activity;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(
                fn ($record) => ActivityResource::getUrl('view', ['record' => $record])
            )

            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),

                TextColumn::make('icon')

            ])

            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                FilamentActions::deleteWithPolicy(),
            ])
            ->toolbarActions([
                FilamentActions::bulkDeleteWithPolicy(Activity::class),
            ]);
    }
}
