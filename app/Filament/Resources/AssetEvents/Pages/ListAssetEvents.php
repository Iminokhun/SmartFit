<?php

namespace App\Filament\Resources\AssetEvents\Pages;

use App\Filament\Resources\AssetEvents\AssetEventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssetEvents extends ListRecords
{
    protected static string $resource = AssetEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
