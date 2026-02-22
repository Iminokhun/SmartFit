<?php

namespace App\Filament\Resources\CustomerSubscriptions\Pages;

use App\Filament\Resources\CustomerSubscriptions\CustomerSubscriptionResource;
use App\Filament\Resources\CustomerSubscriptions\Widgets\CustomerSubscriptionQuickStats;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerSubscriptions extends ListRecords
{
    protected static string $resource = CustomerSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CustomerSubscriptionQuickStats::class,
        ];
    }
}
