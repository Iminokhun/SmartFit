<?php

namespace App\Filament\Resources\Activities\Schemas;

use App\Models\ActivityCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ActivityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Activity Information')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    Select::make('activity_category_id')
                        ->label('Category')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->createOptionForm([
                            TextInput::make('name')
                                ->maxLength(50)
                                ->required()
                        ]),

                    TextInput::make('icon')
                        ->placeholder('heroicon-o-fire')
                        ->nullable(),
                ])
                ->columns(2)
            ]);
    }
}
