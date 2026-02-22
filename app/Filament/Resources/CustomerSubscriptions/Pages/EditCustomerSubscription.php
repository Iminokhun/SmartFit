<?php

namespace App\Filament\Resources\CustomerSubscriptions\Pages;

use App\Filament\Resources\CustomerSubscriptions\CustomerSubscriptionResource;
use Carbon\Carbon;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomerSubscription extends EditRecord
{
    protected static string $resource = CustomerSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn () => auth()->user()?->can('delete', $this->record)),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $status = $data['status'] ?? null;
        if (in_array($status, ['cancelled', 'frozen'], true)) {
            return $data;
        }

        $endDate = $data['end_date'] ?? null;
        $remaining = $data['remaining_visits'] ?? null;

        $isExpired = $endDate && Carbon::parse($endDate)->lt(Carbon::today());
        $noVisits = ($remaining !== null) && ((int) $remaining <= 0);

        if ($isExpired || $noVisits) {
            $data['status'] = 'expired';
        }

        return $data;
    }
}
