<?php

namespace App\Filament\Widgets\Trainer;

use App\Filament\Pages\TrainerSessionAttendance;
use App\Models\Schedule;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TrainerTodaySessionsTable extends BaseWidget
{
    protected static ?string $heading = 'All My Sessions';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        $staffId = auth()->user()?->staff?->id;

        return $table
            ->query(
                Schedule::query()
                    ->where('trainer_id', $staffId ?: 0)
                    ->with(['activity:id,name', 'hall:id,name'])
                    ->orderBy('start_time')
            )
            ->recordUrl(fn (Schedule $record): string => TrainerSessionAttendance::getUrl(['record' => $record->id]))
            ->columns([
                TextColumn::make('activity.name')
                    ->label('Activity')
                    ->searchable(),

                TextColumn::make('hall.name')
                    ->label('Hall')
                    ->searchable(),

                TextColumn::make('days_of_week')
                    ->label('Days')
                    ->formatStateUsing(fn ($state) => collect((array) $state)
                        ->map(fn ($day) => ucfirst((string) $day))
                        ->implode(', '))
                    ->wrap(),

                TextColumn::make('start_time')
                    ->label('Start')
                    ->time('H:i')
                    ->sortable(),

                TextColumn::make('end_time')
                    ->label('End')
                    ->time('H:i')
                    ->sortable(),

                TextColumn::make('max_participants')
                    ->label('Capacity')
                    ->state(fn ($record) => (string) ($record->max_participants ?? 0)),
            ])
            ->filters([
                SelectFilter::make('day')
                    ->label('Day')
                    ->multiple()
                    ->options([
                        'monday' => 'Monday',
                        'tuesday' => 'Tuesday',
                        'wednesday' => 'Wednesday',
                        'thursday' => 'Thursday',
                        'friday' => 'Friday',
                        'saturday' => 'Saturday',
                        'sunday' => 'Sunday',
                    ])
                    ->query(function ($query, array $state) {
                        $days = $state['values'] ?? [];

                        if (empty($days)) {
                            return;
                        }

                        $query->where(function ($subQuery) use ($days) {
                            foreach ($days as $day) {
                                $subQuery->orWhereJsonContains('days_of_week', $day);
                            }
                        });
                    }),

                SelectFilter::make('activity_id')
                    ->label('Activity')
                    ->relationship('activity', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),

                SelectFilter::make('hall_id')
                    ->label('Hall')
                    ->relationship('hall', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
            ])
            ->emptyStateHeading('No sessions found')
            ->emptyStateDescription('Adjust filters or create trainer schedules.')
            ->paginated([10, 25, 50]);
    }
}
