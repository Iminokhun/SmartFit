<?php

namespace App\Filament\Resources\CustomerCheckins\Pages;

use App\Filament\Resources\CustomerCheckins\CustomerCheckinResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomerCheckin extends EditRecord
{
    protected static string $resource = CustomerCheckinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
