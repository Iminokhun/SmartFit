<?php

namespace App\Filament\Resources\Staff\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StaffTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('specialization')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('experience_years')
                    ->label('Experience')
                    ->suffix('yrs'),

                TextColumn::make('salary')
                    ->money('UZS')
                    ->sortable(),

                TextColumn::make('status')

                    ->color([
                        'success' => 'active',
                        'warning' => 'vacation',
                        'gray'    => 'inactive',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state))
            ])
            ->filters([
                //
            ])
            ->recordActions([
                DeleteAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
