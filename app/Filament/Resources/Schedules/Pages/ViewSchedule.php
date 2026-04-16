<?php

namespace App\Filament\Resources\Schedules\Pages;

use App\Filament\Resources\Schedules\ScheduleResource;
use App\Models\Subscription;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class ViewSchedule extends ViewRecord
{
    protected static string $resource = ScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back')
                ->url(ScheduleResource::getUrl('index'))
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),
            EditAction::make(),
            DeleteAction::make()
                ->visible(fn () => auth()->user()?->can('delete', $this->record)),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Schedule Info')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('activity.name')
                                ->label('Activity')
                                ->weight('bold'),

                            TextEntry::make('staff.full_name')
                                ->label('Trainer')
                                ->state(fn ($record) => $record->staff?->full_name ?? '-')
                                ->weight('bold'),

                            TextEntry::make('hall.name')
                                ->label('Hall')
                                ->state(fn ($record) => $record->hall?->name ?? '-')
                                ->weight('bold'),
                        ]),

                        Grid::make(3)->schema([
                            TextEntry::make('days_of_week')
                                ->label('Days')
                                ->state(fn ($record) => self::formatWeekdays($record->days_of_week))
                                ->badge(),

                            TextEntry::make('time_range')
                                ->label('Time')
                                ->state(fn ($record) => $record->time_range ?? '—')
                                ->badge(),

                            TextEntry::make('max_participants')
                                ->label('Capacity')
                                ->state(fn ($record) => $record->max_participants ?? '—')
                                ->badge(),
                        ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Eligible Subscriptions')
                    ->schema([
                        RepeatableEntry::make('eligible_subscriptions')
                            ->label('')
                            ->state(function ($record) {
                                $subscriptions = $record->activity?->subscriptions ?? collect();
                                return self::filterEligibleSubscriptions($subscriptions, $record->days_of_week, $record->start_time, $record->end_time)
                                    ->values();
                            })
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Subscription')
                                    ->weight('bold'),

                                TextEntry::make('visits_limit')
                                    ->label('Visit limit')
                                    ->state(fn ($record) => $record->visits_limit ?? 'Unlimited')
                                    ->badge(),

                                TextEntry::make('allowed_weekdays')
                                    ->label('Allowed days')
                                    ->state(fn ($record) => self::formatWeekdays($record->allowed_weekdays))
                                    ->badge(),

                                TextEntry::make('time_window')
                                    ->label('Allowed time')
                                    ->state(fn ($record) => self::formatTimeWindow($record->time_from, $record->time_to))
                                    ->badge(),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function filterEligibleSubscriptions(Collection $subscriptions, mixed $daysOfWeek, ?string $startTime, ?string $endTime): Collection
    {
        $scheduleDays = is_array($daysOfWeek) ? array_map('intval', $daysOfWeek) : [];
        $scheduleStart = $startTime ? substr((string) $startTime, 0, 8) : null;
        $scheduleEnd = $endTime ? substr((string) $endTime, 0, 8) : null;

        return $subscriptions->filter(function (Subscription $subscription) use ($scheduleDays, $scheduleStart, $scheduleEnd) {
            $allowedDays = is_array($subscription->allowed_weekdays) ? array_map('intval', $subscription->allowed_weekdays) : [];

            if (count($allowedDays) > 0 && count($scheduleDays) > 0) {
                $overlap = array_intersect($allowedDays, $scheduleDays);
                if (count($overlap) === 0) {
                    return false;
                }
            }

            $from = $subscription->time_from ? substr((string) $subscription->time_from, 0, 8) : null;
            $to = $subscription->time_to ? substr((string) $subscription->time_to, 0, 8) : null;

            if ($from && $to && $scheduleStart && $scheduleEnd) {
                if ($scheduleStart < $from || $scheduleEnd > $to) {
                    return false;
                }
            }

            return true;
        });
    }

    private static function formatWeekdays(mixed $value): string
    {
        if (! is_array($value) || count($value) === 0) {
            return 'All days';
        }

        $map = [
            1 => 'Mon',
            2 => 'Tue',
            3 => 'Wed',
            4 => 'Thu',
            5 => 'Fri',
            6 => 'Sat',
            7 => 'Sun',
        ];

        $labels = [];
        foreach (array_map('intval', $value) as $day) {
            if (isset($map[$day])) {
                $labels[] = $map[$day];
            }
        }

        return count($labels) > 0 ? implode(', ', $labels) : 'All days';
    }

    private static function formatTimeWindow(?string $from, ?string $to): string
    {
        if (! $from || ! $to) {
            return 'Any time';
        }

        return substr($from, 0, 5) . ' - ' . substr($to, 0, 5);
    }
}
