<?php

namespace App\Filament\Resources\AssetEvents\Pages;

use App\Filament\Resources\AssetEvents\AssetEventResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAssetEvent extends ViewRecord
{
    protected static string $resource = AssetEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->url(AssetEventResource::getUrl('index'))
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),
            EditAction::make(),
            DeleteAction::make()
                ->visible(fn () => auth()->user()?->role === 'admin'),
        ];
    }
}
