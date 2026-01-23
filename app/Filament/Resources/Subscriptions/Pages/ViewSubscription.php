<?php

namespace App\Filament\Resources\Subscriptions\Pages;

use App\Filament\Resources\Subscriptions\SubscriptionResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewSubscription extends ViewRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->url(SubscriptionResource::getUrl('index'))
                ->color('success')
                ->icon('heroicon-o-home'),
            EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subscription Info')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Name'),

                        TextEntry::make('description')
                            ->placeholder('-'),
                    ]),

                Section::make('Rules')
                    ->schema([
                        TextEntry::make('duration_days')
                            ->label('Duration')
                            ->suffix(' days'),

                        TextEntry::make('visits_limit')
                            ->label('Visit limit')
                        ]),

                Section::make('Activity')
                    ->schema([
                        TextEntry::make('activity.name')
                            ->label('Activity'),
                    ])

            ]);
    }
}
