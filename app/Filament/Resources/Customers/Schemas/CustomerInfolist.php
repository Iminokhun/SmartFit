<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Filament\Resources\Subscriptions\SubscriptionResource;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer Information')
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('photo')
                            ->circular()
                            ->columnSpanFull(),

                        TextEntry::make('full_name')
                            ->label('Full name')
                            ->weight('bold')
                            ->size('lg'),

                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn ($state) => ucfirst((string) $state))
                            ->color(fn ($state) => $state === 'active' ? 'success' : 'gray'),

                        TextEntry::make('phone')
                            ->label('Phone')
                            ->placeholder('-'),

                        TextEntry::make('email')
                            ->label('Email')
                            ->placeholder('-'),

                        TextEntry::make('birth_date')
                            ->label('Birth date')
                            ->date()
                            ->placeholder('-'),

                        TextEntry::make('gender')
                            ->label('Gender')
                            ->formatStateUsing(fn ($state) => ucfirst((string) $state))
                            ->placeholder('-'),
                    ]),

                Section::make('Classes / Visits')
                    ->schema([
                        RepeatableEntry::make('subscriptions')
                            ->label('Customer activities')
                            ->contained(false)
                            ->schema([
                                TextEntry::make('subscription.activity.name')
                                    ->label('Activity')
                                    ->placeholder('-'),

                                TextEntry::make('subscription.name')
                                    ->label('Subscription')
                                    ->weight('bold')
                                    ->color('info')
                                    ->url(fn ($record) => filled($record->subscription_id) ? SubscriptionResource::getUrl('view', ['record' => $record->subscription_id]) : null)
                                    ->openUrlInNewTab()
                                    ->placeholder('-'),

                                TextEntry::make('start_date')
                                    ->label('Start')
                                    ->date()
                                    ->placeholder('-'),

                                TextEntry::make('end_date')
                                    ->label('End')
                                    ->date()
                                    ->placeholder('-'),

                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => ucfirst((string) $state))
                                    ->color(fn ($state) => match ($state) {
                                        'active' => 'success',
                                        'expired' => 'danger',
                                        'frozen' => 'warning',
                                        'cancelled' => 'gray',
                                        default => 'gray',
                                    }),
                            ])
                            ->columns(5),
                    ]),
            ]);
    }
}
