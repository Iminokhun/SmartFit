<?php

namespace App\Filament\Resources\CustomerCheckins;

use App\Filament\Resources\CustomerCheckins\Pages\CreateCustomerCheckin;
use App\Filament\Resources\CustomerCheckins\Pages\EditCustomerCheckin;
use App\Filament\Resources\CustomerCheckins\Pages\ListCustomerCheckins;
use App\Filament\Resources\CustomerCheckins\Schemas\CustomerCheckinForm;
use App\Filament\Resources\CustomerCheckins\Tables\CustomerCheckinsTable;
use App\Models\CustomerCheckin;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CustomerCheckinResource extends Resource
{
    protected static ?string $model = CustomerCheckin::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CustomerCheckinForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerCheckinsTable::configure($table);
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
            'index' => ListCustomerCheckins::route('/'),
            'create' => CreateCustomerCheckin::route('/create'),
            'edit' => EditCustomerCheckin::route('/{record}/edit'),
        ];
    }
}
