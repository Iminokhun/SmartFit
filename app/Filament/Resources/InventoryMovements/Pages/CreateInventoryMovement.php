<?php

namespace App\Filament\Resources\InventoryMovements\Pages;

use App\Filament\Resources\InventoryMovements\InventoryMovementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInventoryMovement extends CreateRecord
{
    protected static string $resource = InventoryMovementResource::class;

    protected function afterCreate(): void
    {
        $movement = $this->record;
        $inventory = $movement->inventory;

        if ($movement->type === 'out') {
            $inventory->decrement('quantity', $movement->quantity);
        } else {
            $inventory->increment('quantity', $movement->quantity);
        }
    }

}
