<?php

namespace App\Observers;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use Illuminate\Validation\ValidationException;

class InventoryMovementObserver
{
    public function creating(InventoryMovement $movement): void
    {
        $inventory = Inventory::query()->findOrFail($movement->inventory_id);

        $this->ensureInventoryAllowsMovement($inventory);

        $delta = $this->signedDelta($movement->type, (int) $movement->quantity);
        $finalQuantity = (int) $inventory->quantity + $delta;

        if ($finalQuantity < 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Not enough stock for this movement.',
            ]);
        }
    }

    public function created(InventoryMovement $movement): void
    {
        $inventory = $movement->inventory;

        if (! $inventory || $inventory->isAsset()) {
            return;
        }

        $this->applyDelta($inventory, $this->signedDelta($movement->type, (int) $movement->quantity));
    }

    public function updating(InventoryMovement $movement): void
    {
        $oldInventoryId = (int) $movement->getOriginal('inventory_id');
        $oldType = (string) $movement->getOriginal('type');
        $oldQuantity = (int) $movement->getOriginal('quantity');
        $oldDelta = $this->signedDelta($oldType, $oldQuantity);

        $newInventoryId = (int) $movement->inventory_id;
        $newType = (string) $movement->type;
        $newQuantity = (int) $movement->quantity;
        $newDelta = $this->signedDelta($newType, $newQuantity);

        $oldInventory = Inventory::query()->findOrFail($oldInventoryId);
        $newInventory = Inventory::query()->findOrFail($newInventoryId);

        $this->ensureInventoryAllowsMovement($newInventory);

        if ($oldInventoryId === $newInventoryId) {
            $baseQuantity = (int) $newInventory->quantity - $oldDelta;
            $finalQuantity = $baseQuantity + $newDelta;

            if ($finalQuantity < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Not enough stock for this update.',
                ]);
            }

            return;
        }

        $newFinalQuantity = (int) $newInventory->quantity + $newDelta;

        if ($newFinalQuantity < 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Not enough stock in selected inventory item.',
            ]);
        }
    }

    public function updated(InventoryMovement $movement): void
    {
        $oldInventoryId = (int) $movement->getOriginal('inventory_id');
        $oldType = (string) $movement->getOriginal('type');
        $oldQuantity = (int) $movement->getOriginal('quantity');
        $oldDelta = $this->signedDelta($oldType, $oldQuantity);

        $newInventoryId = (int) $movement->inventory_id;
        $newDelta = $this->signedDelta((string) $movement->type, (int) $movement->quantity);

        if ($oldInventoryId === $newInventoryId) {
            $inventory = $movement->inventory;
            if ($inventory && ! $inventory->isAsset()) {
                $this->applyDelta($inventory, $newDelta - $oldDelta);
            }

            return;
        }

        $oldInventory = Inventory::query()->find($oldInventoryId);
        $newInventory = $movement->inventory;

        if ($oldInventory && ! $oldInventory->isAsset()) {
            $this->applyDelta($oldInventory, -$oldDelta);
        }

        if ($newInventory && ! $newInventory->isAsset()) {
            $this->applyDelta($newInventory, $newDelta);
        }
    }

    public function deleting(InventoryMovement $movement): void
    {
        $inventory = $movement->inventory;

        if (! $inventory || $inventory->isAsset()) {
            return;
        }

        $revertDelta = -$this->signedDelta((string) $movement->type, (int) $movement->quantity);
        $finalQuantity = (int) $inventory->quantity + $revertDelta;

        if ($finalQuantity < 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Delete would make stock negative. Check movement history.',
            ]);
        }
    }

    public function deleted(InventoryMovement $movement): void
    {
        $inventory = $movement->inventory;

        if (! $inventory || $inventory->isAsset()) {
            return;
        }

        $this->applyDelta($inventory, -$this->signedDelta((string) $movement->type, (int) $movement->quantity));
    }

    private function signedDelta(string $type, int $quantity): int
    {
        return $type === 'out' ? -$quantity : $quantity;
    }

    private function applyDelta(Inventory $inventory, int $delta): void
    {
        if ($delta === 0) {
            return;
        }

        if ($delta > 0) {
            $inventory->increment('quantity', $delta);

            return;
        }

        $inventory->decrement('quantity', abs($delta));
    }

    private function ensureInventoryAllowsMovement(Inventory $inventory): void
    {
        if ($inventory->isAsset()) {
            throw ValidationException::withMessages([
                'inventory_id' => 'Stock movements are not allowed for asset items.',
            ]);
        }
    }
}
