<?php

namespace App\Rules;

use App\Models\Schedule;
use App\Models\Shift;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TrainerShiftAvailabilityRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */

    public function __construct(
        protected ?int $trainerId,
        protected ?string $startTime,
        protected ?string $endTime,
        protected array $daysOfWeek,
        protected ?int $recordId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $trainerId = $this->trainerId ?? $value;
        if (!$trainerId || !$this->startTime || !$this->endTime || empty($this->daysOfWeek)) {
            return;
        }

        $newStart = Carbon::parse($this->startTime)->format('H:i:s');
        $newEnd = Carbon::parse($this->endTime)->format('H:i:s');
        $newStartDisplay = Carbon::parse($this->startTime)->format('H:i');
        $newEndDisplay = Carbon::parse($this->endTime)->format('H:i');

        $shifts = Shift::query()
            ->where('staff_id', $trainerId)
            ->get();

        if ($shifts->isEmpty()) {
            $fail('Trainer has no shifts configured.');
            return;
        }

        $availableByDay = [];
        foreach ($shifts as $shift) {
            $shiftDays = $shift->days_of_week ?? [];
            if (empty($shiftDays)) {
                continue;
            }

            $shiftStart = Carbon::parse($shift->start_time)->format('H:i');
            $shiftEnd = Carbon::parse($shift->end_time)->format('H:i');

            foreach ($shiftDays as $day) {
                $availableByDay[$day][] = "{$shiftStart} - {$shiftEnd}";
            }
        }

        foreach ($availableByDay as $day => $ranges) {
            $availableByDay[$day] = array_values(array_unique($ranges));
        }

        $missingDays = [];
        foreach ($this->daysOfWeek as $day) {
            $hasCoveringShift = $shifts->first(function (Shift $shift) use ($day, $newStart, $newEnd) {
                $shiftDays = $shift->days_of_week ?? [];
                if (!in_array($day, $shiftDays, true)) {
                    return false;
                }

                $shiftStart = Carbon::parse($shift->start_time)->format('H:i:s');
                $shiftEnd = Carbon::parse($shift->end_time)->format('H:i:s');

                return $this->isTimeWithinRange($newStart, $newEnd, $shiftStart, $shiftEnd);
            });

            if (!$hasCoveringShift) {
                $missingDays[] = $day;
            }
        }

        if (!empty($missingDays)) {
            $daysString = implode(', ', array_map('ucfirst', $missingDays));
            $details = [];
            foreach ($missingDays as $day) {
                $label = ucfirst($day);
                $ranges = $availableByDay[$day] ?? [];
                if (empty($ranges)) {
                    $details[] = "{$label}: no shifts";
                    continue;
                }
                $details[] = "{$label}: " . implode(', ', $ranges);
            }
            $detailsString = implode('; ', $details);

            $fail("Trainer shift does not cover {$daysString} at {$newStartDisplay} - {$newEndDisplay}.");
            return;
        }

        // Check for conflicts with other schedules
        $conflict = Schedule::query()
            ->where('trainer_id', $trainerId)
            ->when($this->recordId, fn ($q) => $q->where('id', '!=', $this->recordId))
            ->get()
            ->first(function ($schedule) use ($newStart, $newEnd) {
                $commonDays = array_intersect($this->daysOfWeek, $schedule->days_of_week ?? []);

                if (empty($commonDays)) {
                    return false;
                }

                $schStart = Carbon::parse($schedule->start_time)->format('H:i:s');
                $schEnd = Carbon::parse($schedule->end_time)->format('H:i:s');

                return ($newStart < $schEnd) && ($newEnd > $schStart);
            });

        if ($conflict) {
            $commonDays = array_intersect($this->daysOfWeek, $conflict->days_of_week ?? []);
            $daysString = implode(', ', array_map('ucfirst', $commonDays));
            $conflictStart = Carbon::parse($conflict->start_time)->format('H:i');
            $conflictEnd = Carbon::parse($conflict->end_time)->format('H:i');

            $fail("Trainer already scheduled on {$daysString} at {$conflictStart} - {$conflictEnd}.");
        }
    }


    /**
     * Check if new time range is within shift time range
     */
    private function isTimeWithinRange(string $newStart, string $newEnd, string $shiftStart, string $shiftEnd): bool
    {
        return $newStart >= $shiftStart && $newEnd <= $shiftEnd;
    }
}
