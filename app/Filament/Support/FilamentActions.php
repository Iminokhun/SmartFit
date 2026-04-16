<?php

namespace App\Filament\Support;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;

class FilamentActions
{
    /**
     * DeleteAction visible only when the auth user has 'delete' policy on the record.
     */
    public static function deleteWithPolicy(): DeleteAction
    {
        return DeleteAction::make()
            ->visible(fn ($record) => auth()->user()?->can('delete', $record));
    }

    /**
     * EditAction visible only when the auth user has 'update' policy on the record.
     */
    public static function editWithPolicy(): EditAction
    {
        return EditAction::make()
            ->visible(fn ($record) => auth()->user()?->can('update', $record));
    }

    /**
     * BulkActionGroup with a DeleteBulkAction guarded by 'deleteAny' policy.
     *
     * @param  class-string  $modelClass
     */
    public static function bulkDeleteWithPolicy(string $modelClass): BulkActionGroup
    {
        return BulkActionGroup::make([
            DeleteBulkAction::make()
                ->visible(fn () => auth()->user()?->can('deleteAny', $modelClass)),
        ]);
    }
}
