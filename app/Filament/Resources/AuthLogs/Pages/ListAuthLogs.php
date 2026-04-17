<?php

namespace App\Filament\Resources\AuthLogs\Pages;

use App\Filament\Resources\AuthLogs\AuthLogResource;
use Filament\Resources\Pages\ListRecords;

class ListAuthLogs extends ListRecords
{
    protected static string $resource = AuthLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

