<?php

namespace App\Filament\Resources\AssetEvents\Pages;

use App\Filament\Resources\AssetEvents\AssetEventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAssetEvent extends CreateRecord
{
    protected static string $resource = AssetEventResource::class;
}
