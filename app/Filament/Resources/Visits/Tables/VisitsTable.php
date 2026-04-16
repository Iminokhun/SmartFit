<?php

namespace App\Filament\Resources\Visits\Tables;

use App\Filament\Support\FilamentColumns;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VisitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.full_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('schedule.id')
                    ->label('Schedule')
                    ->sortable(),

                TextColumn::make('visited_at')
                    ->dateTime('d/m/Y H:i')
                    ->label('Visited at')
                    ->sortable(),

                FilamentColumns::statusBadge('status', [
                    'visited'   => 'success',
                    'missed'    => 'danger',
                    'cancelled' => 'warning',
                ]),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
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

