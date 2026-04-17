<?php

namespace App\Filament\Resources\InventoryCategories;

use App\Enums\InventoryStatus;
use App\Filament\Resources\Inventories\InventoryResource;
use App\Filament\Resources\InventoryCategories\Pages\ManageInventoryCategories;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InventoryCategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Inventories';
    protected static ?int $navigationSort = 4;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;
    protected static ?string $navigationLabel = 'Inventory Categories';
    protected static ?int $pollingInterval = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true)
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->withSum('inventories as total_quantity', 'quantity')

            ->withSum([
                'inventories as available_quantity' => fn ($q) =>
                $q->where('status', InventoryStatus::Available->value),
            ], 'quantity')

            ->withSum([
                'inventories as repair_quantity' => fn ($q) =>
                $q->where('status', InventoryStatus::Repair->value),
            ], 'quantity')

            ->withSum([
                'inventories as written_off_quantity' => fn ($q) =>
                $q->where('status', InventoryStatus::WrittenOff->value),
            ], 'quantity');
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total_quantity')
                    ->label('Total')
                    ->badge()
                    ->color('gray')
                    ->url(fn ($record) =>
                    InventoryResource::getUrl('index', [
                        'tableFilters' => [
                            'category_id' => [
                                'value' => $record->id,
                            ],
                        ],
                    ])
                    )
                    ->openUrlInNewTab(),

                TextColumn::make('available_quantity')
                    ->label('Available')
                    ->badge()
                    ->color('success'),

                TextColumn::make('repair_quantity')
                    ->label('Repair')
                    ->badge()
                    ->color('warning'),

                TextColumn::make('written_off_quantity')
                    ->label('Written Off')
                    ->badge()
                    ->color('danger'),
            ])
            ->filters([
              //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageInventoryCategories::route('/'),
        ];
    }
}
