<?php

namespace App\Filament\Resources\AuthLogs;

use App\Filament\Resources\AuthLogs\Pages\ListAuthLogs;
use App\Filament\Resources\AuthLogs\Tables\AuthLogsTable;
use App\Models\AuthLog;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AuthLogResource extends Resource
{
    protected static ?string $model = AuthLog::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Security';
    protected static ?int $navigationSort = 1;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;
    protected static ?string $navigationLabel = 'Auth Logs';
    protected static ?string $pluralModelLabel = 'Auth Logs';

    public static function table(Table $table): Table
    {
        return AuthLogsTable::configure($table);
    }

    public static function canViewAny(): bool
    {
        return self::isAdmin(auth()->user());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuthLogs::route('/'),
        ];
    }

    private static function isAdmin(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        $roleName = strtolower((string) ($user->role?->name ?? ''));

        return $roleName === 'admin' || $user->role_id === 1;
    }
}

