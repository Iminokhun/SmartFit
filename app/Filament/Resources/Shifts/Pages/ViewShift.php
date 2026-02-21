<?php

namespace App\Filament\Resources\Shifts\Pages;

use App\Filament\Resources\Shifts\ShiftResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewShift extends ViewRecord
{
    protected static string $resource = ShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->url(ShiftResource::getUrl('index'))
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}

