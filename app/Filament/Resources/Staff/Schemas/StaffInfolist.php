<?php

namespace App\Filament\Resources\Staff\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
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

                    TextEntry::make('full_name'),

                    TextEntry::make('specialization'),

                    TextEntry::make('experience_years')
                        ->suffix('years'),

                    TextEntry::make('phone'),

                    TextEntry::make('email'),

                    TextEntry::make('salary')
                        ->money('UZS'),

                    TextEntry::make('salary_type')
                        ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state))),

                    TextEntry::make('status')
                        ->badge()
                        ->colors([
                            'success' => 'active',
                            'warning' => 'vacation',
                            'gray'    => 'inactive',
                        ])
                        ->formatStateUsing(fn ($state) => ucfirst($state)),

                ])
            ]);
    }
}
