<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subscription Info')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('description')
                            ->rows(3),
                    ])
                    ->columns(2),

                Section::make('Rules')
                    ->schema([
                        TextInput::make('duration_days')
                            ->numeric()
                            ->required()
                            ->suffix('Days'),

                        TextInput::make('visits_limit')
                            ->numeric()
                            ->required()
                    ])
                    ->columns(2),

                CheckboxList::make('allowed_weekdays')
                    ->label('Allowed weekdays')
                    ->options([
                        1 => 'Mon',
                        2 => 'Tue',
                        3 => 'Wed',
                        4 => 'Thu',
                        5 => 'Fri',
                        6 => 'Sat',
                    ])
                    ->columns(4)
                    ->helperText('Leave empty = all days')
                    ->nullable(),

                TimePicker::make('time_from')
                    ->label('Allowed from')
                    ->seconds(false)
                    ->nullable(),

                TextInput::make('max_checkins_per_day')
                    ->label('Max check-ins per day')
                    ->numeric()
                    ->minValue(1)
                    ->nullable()
                    ->helperText('Leave empty = unlimited'),

                TimePicker::make('time_to')
                    ->label('Allowed to')
                    ->seconds(false)
                    ->nullable()
                    ->rule(function (callable $get) {
                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                            $from = $get('time_from');
                            if ($from && $value && $value <= $from) {
                                $fail('Time to must be later than time from.');
                            }
                        };
                    }),

                Select::make('trainer_id')
                    ->label('Trainer')
                    ->relationship(
                        'trainer',
                        'full_name',
                        fn(Builder $query) => $query->whereHas('role', fn(Builder $role) => $role->where('name', 'Trainer'))
                    )
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('activity_id')
                    ->relationship('activity', 'name')
                    ->preload()
                    ->required(),

                Select::make('hall_id')
                    ->label('Hall')
                    ->relationship('hall', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('schedule_max_participants')
                    ->label('Max participants (for schedule)')
                    ->numeric()
                    ->required()
                    ->dehydrated(false),

                Section::make('Pricing')
                    ->schema([
                        TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('UZS'),

                        TextInput::make('discount')
                            ->numeric()
                            ->suffix('%')
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100),
                    ]),

            ]);
    }
}
