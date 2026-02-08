<?php

namespace App\Rules;

use App\Models\Schedule;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class HallAvailabilityRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function __construct(
        protected ?string $startTime,
        protected ?string $endTime,
        protected array $daysOfWeek,
        protected mixed $recordId = null
    ) {}
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value || !$this->startTime || !$this->endTime || empty($this->daysOfWeek)) {
            return;
        }

        $newStart = Carbon::parse($this->startTime)->format('H:i:s');
        $newEnd = Carbon::parse($this->endTime)->format('H:i:s');

        // Ищем пересечения
        $conflict = Schedule::query()
            ->where('hall_id', $value)
            ->when($this->recordId, fn ($q) => $q->where('id', '!=', $this->recordId))
            ->get()
            ->first(function ($schedule) use ($newStart, $newEnd) {
                // Проверяем дни недели
                $commonDays = array_intersect($this->daysOfWeek, $schedule->days_of_week ?? []);
                if (empty($commonDays)) {
                    return false;
                }

                // Проверяем время: (StartA < EndB) AND (EndA > StartB)
                $schStart = Carbon::parse($schedule->start_time)->format('H:i:s');
                $schEnd = Carbon::parse($schedule->end_time)->format('H:i:s');

                return ($newStart < $schEnd) && ($newEnd > $schStart);
            });

        if ($conflict) {
            $commonDays = array_intersect($this->daysOfWeek, $conflict->days_of_week ?? []);
            $daysString = implode(', ', array_map('ucfirst', $commonDays));

            $fail("This hall is already booked on {$daysString} at {$conflict->start_time} - {$conflict->end_time} for '{$conflict->activity->name}'.");
        }
    }
}
