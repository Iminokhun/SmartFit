<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Customers\Widgets\CustomerQuickStats;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected static ?string $title = 'Сustomers';

    protected ?string $heading = 'Customer List';
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
            ->createAnother(false),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CustomerQuickStats::class,
        ];
    }
}
