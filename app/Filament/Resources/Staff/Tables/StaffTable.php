<?php

namespace App\Filament\Resources\Staff\Tables;

use App\Filament\Resources\Staff\StaffResource;
use App\Models\Staff;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StaffTable
{
    public static function configure(Table $table): Table
    {
        return $table

            ->recordUrl(
                fn ($record) => StaffResource::getUrl('view', ['record' => $record])
            )
            ->columns([
                TextColumn::make('full_name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('specialization')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('experience_years')
                    ->label('Experience')
                    ->suffix('yrs'),

                TextColumn::make('salary')
                    ->money('UZS')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->size('large')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'vacation',
                        'gray'    => 'inactive',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->multiple()
                    ->options([
                        'active' => 'Active',
                        'vacation' => 'Vacation',
                        'inactive' => 'Inactive',
                    ]),

                SelectFilter::make('role_id')
                    ->label('Role')
                    ->relationship('role', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),

                SelectFilter::make('salary_type')
                    ->label('Salary Type')
                    ->multiple()
                    ->options([
                        'fixed' => 'Fixed',
                        'percent' => 'Percent',
                        'per_session' => 'Per Session',
                    ]),

                Filter::make('specialization')
                    ->form([
                        Select::make('specialization')
                            ->label('Specialization')
                            ->options(fn () => Staff::query()
                                ->whereNotNull('specialization')
                                ->distinct()
                                ->orderBy('specialization')
                                ->pluck('specialization', 'specialization')
                                ->all())
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(function ($query, array $data) {
                        if (blank($data['specialization'] ?? null)) {
                            return $query;
                        }

                        return $query->where('specialization', $data['specialization']);
                    }),

                Filter::make('salary_range')
                    ->label('Salary range')
                    ->form([
                        TextInput::make('salary_from')
                            ->label('Salary from')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('salary_to')
                            ->label('Salary to')
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->query(function ($query, array $data) {
                        if (filled($data['salary_from'] ?? null)) {
                            $query->where('salary', '>=', (float) $data['salary_from']);
                        }

                        if (filled($data['salary_to'] ?? null)) {
                            $query->where('salary', '<=', (float) $data['salary_to']);
                        }

                        return $query;
                    }),
            ])
            ->recordActions([
                DeleteAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
