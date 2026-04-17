<?php

namespace App\Observers;

use App\Enums\AssetEventType;
use App\Enums\InventoryStatus;
use App\Models\AssetEvent;
use Illuminate\Validation\ValidationException;

class AssetEventObserver
{
    public function creating(AssetEvent $event): void
    {
        $inventory = $event->inventory;

        if (! $inventory || ! $inventory->isAsset()) {
            throw ValidationException::withMessages([
                'inventory_id' => 'Asset event can be created only for asset items.',
            ]);
        }

        $eventType = $event->event_type instanceof AssetEventType
            ? $event->event_type
            : AssetEventType::from((string) $event->event_type);

        $currentStatus = $inventory->status;

        if ($currentStatus === InventoryStatus::Repair && $eventType !== AssetEventType::ReturnedFromRepair) {
            throw ValidationException::withMessages([
                'event_type' => 'When asset is in repair, only "Returned from repair" event is allowed.',
            ]);
        }

        if ($currentStatus !== InventoryStatus::Repair && $eventType === AssetEventType::ReturnedFromRepair) {
            throw ValidationException::withMessages([
                'event_type' => '"Returned from repair" can be used only when asset status is Repair.',
            ]);
        }

        $event->condition_before = $inventory->condition;
        $event->status_before = $inventory->status?->value;

        // From hall always follows the current asset location.
        $event->from_hall_id = $inventory->hall_id;

        [$statusAfter, $conditionAfter, $toHallId] = $this->resolveAfterState($event, $inventory->hall_id);

        $event->status_after = $statusAfter;
        $event->condition_after = $conditionAfter;
        $event->to_hall_id = $toHallId;
    }

    public function created(AssetEvent $event): void
    {
        $inventory = $event->inventory;

        if (! $inventory) {
            return;
        }

        $inventory->update([
            'status' => $event->status_after,
            'condition' => $event->condition_after,
            'hall_id' => $event->to_hall_id,
        ]);
    }

    private function resolveAfterState(AssetEvent $event, ?int $currentHallId): array
    {
        $eventType = $event->event_type instanceof AssetEventType
            ? $event->event_type
            : AssetEventType::from((string) $event->event_type);

        $statusAfter = $event->status_after instanceof InventoryStatus
            ? $event->status_after->value
            : (is_numeric($event->status_after) ? (int) $event->status_after : null);

        $conditionAfter = $event->condition_after;
        $toHallId = $event->to_hall_id ?: $currentHallId;

        switch ($eventType) {
            case AssetEventType::Commissioned:
                $statusAfter ??= InventoryStatus::Available->value;
                $conditionAfter ??= 'new';
                break;
            case AssetEventType::Transferred:
                if (! $event->to_hall_id) {
                    throw ValidationException::withMessages([
                        'to_hall_id' => 'To hall is required for transfer events.',
                    ]);
                }
                $statusAfter ??= InventoryStatus::Available->value;
                $conditionAfter ??= $event->condition_before;
                break;
            case AssetEventType::SentToRepair:
                $statusAfter ??= InventoryStatus::Repair->value;
                $conditionAfter ??= 'repair';
                break;
            case AssetEventType::ReturnedFromRepair:
                $statusAfter ??= InventoryStatus::Available->value;
                $conditionAfter ??= 'good';
                break;
            case AssetEventType::WrittenOff:
                $statusAfter ??= InventoryStatus::WrittenOff->value;
                $conditionAfter ??= 'damaged';
                break;
        }

        return [$statusAfter, $conditionAfter, $toHallId];
    }
}

