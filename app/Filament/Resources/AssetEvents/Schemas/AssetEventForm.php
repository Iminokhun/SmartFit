<?php

namespace App\Filament\Resources\AssetEvents\Schemas;

use App\Enums\AssetEventType;
use App\Enums\InventoryItemType;
use App\Enums\InventoryStatus;
use App\Models\Inventory;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class AssetEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('inventory_id')
                    ->label('Asset')
                    ->relationship(
                        name: 'inventory',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->where('item_type', InventoryItemType::Asset->value),
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} | Tag: " . ($record->asset_tag ?: '-') . " | Serial: " . ($record->serial_number ?: '-'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(),

                Placeholder::make('from_hall_auto')
                    ->label('From hall')
                    ->content(function (Get $get): string {
                        $inventoryId = $get('inventory_id');
                        if (! $inventoryId) {
                            return 'Auto from selected asset';
                        }

                        $inventory = Inventory::query()->with('hall')->find($inventoryId);

                        return $inventory?->hall?->name ?: 'No hall assigned';
                    }),

                Select::make('event_type')
                    ->label('Event type')
                    ->options(function (Get $get): array {
                        $inventoryId = $get('inventory_id');

                        if (! $inventoryId) {
                            return AssetEventType::options();
                        }

                        $inventory = Inventory::query()->find($inventoryId);

                        if (! $inventory) {
                            return AssetEventType::options();
                        }

                        if ($inventory->status === InventoryStatus::Repair) {
                            return [
                                AssetEventType::ReturnedFromRepair->value => AssetEventType::ReturnedFromRepair->label(),
                            ];
                        }

                        return collect(AssetEventType::cases())
                            ->reject(fn (AssetEventType $case) => $case === AssetEventType::ReturnedFromRepair)
                            ->mapWithKeys(fn (AssetEventType $case) => [$case->value => $case->label()])
                            ->all();
                    })
                    ->required()
                    ->live(),

                DateTimePicker::make('event_date')
                    ->seconds(false)
                    ->default(now())
                    ->required(),

                Select::make('to_hall_id')
                    ->label('To hall')
                    ->relationship('toHall', 'name')
                    ->searchable()
                    ->preload()
                    ->required(fn (Get $get) => $get('event_type') === AssetEventType::Transferred->value)
                    ->visible(fn (Get $get) => in_array($get('event_type'), [AssetEventType::Transferred->value, AssetEventType::Commissioned->value], true)),

                Select::make('status_after')
                    ->label('Status after')
                    ->options(InventoryStatus::options())
                    ->helperText('Optional: leave empty to use automatic status by event type.'),

                TextInput::make('condition_after')
                    ->label('Condition after')
                    ->helperText('Optional: leave empty to use automatic condition by event type.'),

                Textarea::make('note')
                    ->label('Note')
                    ->rows(3),
            ]);
    }
}
