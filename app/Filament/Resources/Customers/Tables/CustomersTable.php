<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn ($record) => CustomerResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('full_name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('phone')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('birth_date')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('gender')
                    ->formatStateUsing(fn ($state) => Str::ucfirst($state))
                    ->badge()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Str::ucfirst($state))
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                        'warning' => 'blocked',
                        'gray' => 'deleted',
                    ])
                    ->searchable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'blocked' => 'Blocked',
                        'deleted' => 'Deleted',
                    ]),

                Filter::make('created_at')
                    ->label('Created range')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): void {
                        if (! empty($data['from'])) {
                            $query->whereDate('created_at', '>=', $data['from']);
                        }

                        if (! empty($data['until'])) {
                            $query->whereDate('created_at', '<=', $data['until']);
                        }
                    }),
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
}
