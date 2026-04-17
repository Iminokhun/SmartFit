<?php

namespace App\Filament\Resources\Staff\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StaffInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Staff Information')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    ImageEntry::make('photo')
                        ->circular()
                        ->columnSpanFull(),

                    TextEntry::make('full_name')
                        ->weight('bold')
                        ->size('lg'),

                    TextEntry::make('specialization')
                        ->weight('bold')
                        ->size('lg'),

                    TextEntry::make('experience_years')
                        ->suffix(' years')
                        ->size('lg')
                        ->weight('bold'),

                    TextEntry::make('phone')
                        ->label('Phone number')
                        ->weight('bold')
                        ->size('lg')
                        ->icon('heroicon-m-phone'),

                    TextEntry::make('salary_type')
                        ->weight('bold')
                        ->size('lg')
                        ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state))),

                    TextEntry::make('salary')
                        ->weight('bold')
                        ->size('lg')
                        ->money(),

                    TextEntry::make('email')
                        ->icon('heroicon-m-envelope'),

                    TextEntry::make('status')
                        ->badge()
                        ->size('large')
                        ->colors([
                            'success' => 'active',
                            'warning' => 'vacation',
                            'gray'    => 'inactive',
                        ])
                        ->formatStateUsing(fn ($state) => ucfirst($state)),

                    RepeatableEntry::make('shifts')
                        ->label('Working Schedule')
                        ->schema([
                            TextEntry::make('days_of_week')
                                ->label('Days')
                                ->badge()
                                ->weight('bold')
                                ->color('primary')
                                ->separator(', ')
                                ->formatStateUsing(fn ($state) => ucfirst($state)),

                            Grid::make(2)->schema([
                                TextEntry::make('start_time')
                                    ->label('From')
                                    ->weight('bold')
                                    ->time('H:i'),

                                TextEntry::make('end_time')
                                    ->label('To')
                                    ->weight('bold')
                                    ->time('H:i'),
                            ]),
                        ]),
                ]),
                    Section::make('Professional Activities')
                        ->visible(fn ($record) => strtolower($record->role?->name ?? '') === 'trainer')
                        ->columnSpanFull()
                        ->schema([
                            RepeatableEntry::make('schedules')
                            ->label('Activities')
                                ->grid(2)
                                ->schema([
                                    TextEntry::make('activity.name')
                                    ->label('Activity')
                                        ->weight('bold')
                                        ->color('primary'),

                                    TextEntry::make('hall.name')
                                        ->label('Location')
                                        ->icon('heroicon-m-map-pin'),

                                    TextEntry::make('days_of_week')
                                        ->label('Days')
                                        ->badge()
                                        ->separator(', ')
                                        ->formatStateUsing(fn ($state) => ucfirst($state)),

                                    TextEntry::make('time_range')
                                        ->label('Time Slot')
                                ])
                        ])
            ]);
    }
}
