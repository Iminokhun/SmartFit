<?php

namespace App\Filament\Resources\Schedules\Pages;

use App\Filament\Resources\Schedules\ScheduleResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSchedule extends ViewRecord
{
    protected static string $resource = ScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->url(ScheduleResource::getUrl('index'))
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}

