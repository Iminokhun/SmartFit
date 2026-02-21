<?php

namespace App\Filament\Resources\AssetEvents\Pages;

use App\Filament\Resources\AssetEvents\AssetEventResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAssetEvent extends EditRecord
{
    protected static string $resource = AssetEventResource::class;

    public function mount(int|string $record): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => auth()->user()?->role === 'admin'),
        ];
    }
}
