<?php

namespace App\Filament\Resources\Halls;

use App\Filament\Resources\Halls\Pages\CreateHall;
use App\Filament\Resources\Halls\Pages\EditHall;
use App\Filament\Resources\Halls\Pages\ListHalls;
use App\Filament\Resources\Halls\Schemas\HallForm;
use App\Filament\Resources\Halls\Tables\HallsTable;
use App\Models\Hall;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HallResource extends Resource
{
    protected static ?string $model = Hall::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Scheduling';
    protected static ?int $navigationSort = 1;
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-building-office';

    public static function form(Schema $schema): Schema
    {
        return HallForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HallsTable::configure($table);
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
            'index' => ListHalls::route('/'),
            'create' => CreateHall::route('/create'),
            'edit' => EditHall::route('/{record}/edit'),
        ];
    }
}
