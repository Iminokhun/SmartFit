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
        abort_unless($this->canManage(), 403);

        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => auth()->user()?->can('delete', $this->record)),
        ];
    }

    private function canManage(): bool
    {
        $user = auth()->user();
        $roleName = strtolower((string) ($user?->role?->name ?? ''));

        return in_array($roleName, ['admin', 'manager'], true) || in_array((int) ($user?->role_id ?? 0), [1, 2], true);
    }
}
