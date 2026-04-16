<?php

namespace App\Filament\Resources\CustomerCheckins\Pages;

use App\Filament\Resources\CustomerCheckins\CustomerCheckinResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerCheckin extends CreateRecord
{
    protected static string $resource = CustomerCheckinResource::class;
}
