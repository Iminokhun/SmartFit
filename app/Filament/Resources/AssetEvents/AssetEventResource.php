<?php

namespace App\Filament\Resources\AssetEvents;

use App\Filament\Resources\AssetEvents\Pages\CreateAssetEvent;
use App\Filament\Resources\AssetEvents\Pages\EditAssetEvent;
use App\Filament\Resources\AssetEvents\Pages\ListAssetEvents;
use App\Filament\Resources\AssetEvents\Pages\ViewAssetEvent;
use App\Filament\Resources\AssetEvents\Schemas\AssetEventForm;
use App\Filament\Resources\AssetEvents\Tables\AssetEventsTable;
use App\Models\AssetEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AssetEventResource extends Resource
{
    protected static ?string $model = AssetEvent::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Inventories';
    protected static ?int $navigationSort = 3;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;
    protected static ?string $navigationLabel = 'Asset Events';

    public static function form(Schema $schema): Schema
    {
        return AssetEventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssetEventsTable::configure($table);
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
            'index' => ListAssetEvents::route('/'),
            'create' => CreateAssetEvent::route('/create'),
            'view' => ViewAssetEvent::route('/{record}'),
            'edit' => EditAssetEvent::route('/{record}/edit'),
        ];
    }
}
