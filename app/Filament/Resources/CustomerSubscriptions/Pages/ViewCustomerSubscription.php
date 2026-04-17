<?php

namespace App\Filament\Resources\CustomerSubscriptions\Pages;

use App\Filament\Resources\CustomerSubscriptions\CustomerSubscriptionResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerSubscription extends ViewRecord
{
    protected static string $resource = CustomerSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->url(CustomerSubscriptionResource::getUrl('index'))
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),
            EditAction::make(),
            DeleteAction::make()
                ->visible(fn () => auth()->user()?->can('delete', $this->record)),
        ];
    }
}
