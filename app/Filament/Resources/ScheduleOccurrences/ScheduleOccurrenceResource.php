<?php

namespace App\Filament\Resources\ScheduleOccurrences;

use App\Filament\Resources\ScheduleOccurrences\Pages\CreateScheduleOccurrence;
use App\Filament\Resources\ScheduleOccurrences\Pages\EditScheduleOccurrence;
use App\Filament\Resources\ScheduleOccurrences\Pages\ListScheduleOccurrences;
use App\Filament\Resources\ScheduleOccurrences\Schemas\ScheduleOccurrenceForm;
use App\Filament\Resources\ScheduleOccurrences\Tables\ScheduleOccurrencesTable;
use App\Models\ScheduleOccurrence;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ScheduleOccurrenceResource extends Resource
{
    protected static ?string $model = ScheduleOccurrence::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Attendance';
    protected static ?int $navigationSort = 2;
    protected static string|null|BackedEnum $navigationIcon = Heroicon::OutlinedCalendar;

    public static function form(Schema $schema): Schema
    {
        return ScheduleOccurrenceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScheduleOccurrencesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\ScheduleOccurrences\RelationManagers\VisitsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListScheduleOccurrences::route('/'),
            'create' => CreateScheduleOccurrence::route('/create'),
            'edit' => EditScheduleOccurrence::route('/{record}/edit'),
        ];
    }
}

