<?php

namespace App\Filament\Resources\Staff\Pages;

use App\Filament\Resources\Staff\StaffResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewStaff extends ViewRecord
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->url(StaffResource::getUrl('index'))
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),
            EditAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return $this->record->full_name;
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->columns([
                        'default' => 1,
                        'lg' => 3,
                    ])
                    ->schema([
                        Group::make([
                            Section::make('Personal Info')
                                ->icon('heroicon-m-user-circle')
                                ->schema([
                                    ImageEntry::make('photo')
                                        ->circular()
                                        ->columnSpanFull(),

                                    Grid::make(2)->schema([
                                        TextEntry::make('full_name')
                                            ->label('Full Name')
                                            ->icon('heroicon-m-user')
                                            ->weight('bold')
                                            ->size('lg'),

                                        TextEntry::make('specialization')
                                            ->label('Specialization')
                                            ->icon('heroicon-m-academic-cap')
                                            ->weight('bold')
                                            ->size('lg'),
                                    ]),

                                    Grid::make(2)->schema([
                                        TextEntry::make('phone')
                                            ->label('Phone')
                                            ->icon('heroicon-m-phone'),

                                        TextEntry::make('email')
                                            ->label('Email')
                                            ->icon('heroicon-m-envelope'),
                                    ]),

                                    TextEntry::make('experience_years')
                                        ->label('Experience')
                                        ->icon('heroicon-m-clock')
                                        ->suffix(' years')
                                        ->weight('bold'),
                                ]),

                            Section::make('Compensation')
                                ->icon('heroicon-m-banknotes')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextEntry::make('salary_type')
                                            ->label('Salary Type')
                                            ->icon('heroicon-m-tag')
                                            ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state))),

                                        TextEntry::make('salary')
                                            ->label('Salary')
                                            ->icon('heroicon-m-currency-dollar')
                                            ->money('UZS')
                                            ->weight('bold')
                                            ->color('success'),
                                    ]),
                                ]),

                            Section::make('Working Schedule')
                                ->icon('heroicon-m-clock')
                                ->schema([
                                    RepeatableEntry::make('shifts')
                                        ->label('')
                                        ->schema([
                                            TextEntry::make('days_of_week')
                                                ->label('Days')
                                                ->badge()
                                                ->color('primary')
                                                ->separator(', ')
                                                ->formatStateUsing(fn ($state) => ucfirst($state)),

                                            Grid::make(2)->schema([
                                                TextEntry::make('start_time')
                                                    ->label('From')
                                                    ->time('H:i'),

                                                TextEntry::make('end_time')
                                                    ->label('To')
                                                    ->time('H:i'),
                                            ]),
                                        ]),
                                ]),

                            Section::make('Professional Activities')
                                ->icon('heroicon-m-academic-cap')
                                ->visible(fn ($record) => strtolower($record->role?->name ?? '') === 'trainer')
                                ->schema([
                                    RepeatableEntry::make('schedules')
                                        ->label('')
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
                                                ->label('Time Slot'),
                                        ]),
                                ]),
                        ])
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 2,
                            ]),

                        Group::make([
                            Section::make('Status')
                                ->icon('heroicon-m-information-circle')
                                ->schema([
                                    TextEntry::make('status')
                                        ->label('Status')
                                        ->badge()
                                        ->colors([
                                            'success' => 'active',
                                            'warning' => 'vacation',
                                            'gray'    => 'inactive',
                                        ])
                                        ->formatStateUsing(fn ($state) => ucfirst($state)),

                                    TextEntry::make('role.name')
                                        ->label('Role')
                                        ->badge()
                                        ->color('info'),
                                ]),

                            Section::make('Details')
                                ->icon('heroicon-m-identification')
                                ->schema([
                                    TextEntry::make('experience_years')
                                        ->label('Experience')
                                        ->icon('heroicon-m-clock')
                                        ->suffix(' years')
                                        ->weight('bold'),

                                    TextEntry::make('salary_type')
                                        ->label('Salary type')
                                        ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state))),
                                ]),
                        ])
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 1,
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
