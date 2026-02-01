<?php

namespace App\Filament\Widgets;

use App\Enums\InventoryStatus;
use App\Models\Inventory;
use Filament\Widgets\ChartWidget;

class InventoryStatusChart extends ChartWidget
{
    protected ?string $heading = 'Inventory Status Chart';

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'data' => [
                        Inventory::where('status', InventoryStatus::Available)->sum('quantity'),
                        Inventory::where('status', InventoryStatus::Repair)->sum('quantity'),
                        Inventory::where('status', InventoryStatus::WrittenOff)->sum('quantity'),
                    ],
                ],
            ],
            'labels' => [
                'Available',
                'Repair',
                'Written Off',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    public function getWidgets(): array
    {
        return [
            InventoryStatusChart::class,
        ];
    }
}
