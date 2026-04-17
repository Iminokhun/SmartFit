<?php

namespace App\Filament\Resources\AuthLogs\Tables;

use App\Filament\Support\FilamentFilters;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuthLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('At')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => $state === 'success' ? 'success' : 'danger')
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),

                TextColumn::make('panel')
                    ->badge()
                    ->placeholder('-'),

                TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('email')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('ip_address')
                    ->label('IP')
                    ->placeholder('-'),

                TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->limit(70)
                    ->tooltip(fn ($record) => $record->user_agent)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->multiple()
                    ->options([
                        'success' => 'Success',
                        'fail' => 'Fail',
                    ]),

                SelectFilter::make('panel')
                    ->multiple()
                    ->options([
                        'admin' => 'Admin',
                        'manager' => 'Manager',
                    ]),

                FilamentFilters::dateRange(),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}

