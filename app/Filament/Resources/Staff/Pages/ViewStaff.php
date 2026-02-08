<?php

namespace App\Filament\Resources\Staff\Pages;

use App\Filament\Resources\CustomerSubscriptions\CustomerSubscriptionResource;
use App\Filament\Resources\Staff\StaffResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStaff extends ViewRecord
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->url(StaffResource::getUrl('index'))
                ->color('success')
                ->icon('heroicon-o-home'),
            EditAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return $this->record->full_name;
    }
}
