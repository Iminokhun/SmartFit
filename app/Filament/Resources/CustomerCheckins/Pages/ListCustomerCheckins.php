<?php

namespace App\Filament\Resources\CustomerCheckins\Pages;

use App\Filament\Resources\CustomerCheckins\CustomerCheckinResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerCheckins extends ListRecords
{
    protected static string $resource = CustomerCheckinResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
