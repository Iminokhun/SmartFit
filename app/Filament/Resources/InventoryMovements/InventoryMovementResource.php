<?php

namespace App\Filament\Resources\InventoryMovements;

use App\Filament\Resources\InventoryMovements\Pages\CreateInventoryMovement;
use App\Filament\Resources\InventoryMovements\Pages\EditInventoryMovement;
use App\Filament\Resources\InventoryMovements\Pages\ListInventoryMovements;
use App\Filament\Resources\InventoryMovements\Pages\ViewInventoryMovement;
use App\Filament\Resources\InventoryMovements\Schemas\InventoryMovementForm;
use App\Filament\Resources\InventoryMovements\Tables\InventoryMovementsTable;
use App\Models\InventoryMovement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InventoryMovementResource extends Resource
{
    protected static ?string $model = InventoryMovement::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Inventories';
    protected static ?int $navigationSort = 2;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsUpDown;

    public static function form(Schema $schema): Schema
    {
        return InventoryMovementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InventoryMovementsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventoryMovements::route('/'),
            'create' => CreateInventoryMovement::route('/create'),
            'view' => ViewInventoryMovement::route('/{record}'),
            'edit' => EditInventoryMovement::route('/{record}/edit'),
        ];
    }
}
