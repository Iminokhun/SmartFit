<?php

namespace App\Filament\Resources\Subscriptions\Pages;

use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Models\Schedule;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSubscription extends CreateRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $subscription = parent::handleRecordCreation($data);

        $map = [
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
        ];

        $allowed = $subscription->allowed_weekdays ?? [];

        if (empty($allowed)) {
            $days = array_values($map);
        } else {
            $days = array_values(array_filter(array_map(
                fn ($d) => $map[(int) $d] ?? null,
                $allowed
            )));
        }
        $maxParticipants = (int) ($state['schedule_max_participants'] ?? 0);

        Schedule::create([
            'subscription_id' => $subscription->id,
            'activity_id' => $subscription->activity_id,
            'trainer_id' => $subscription->trainer_id,
            'hall_id' => $subscription->hall_id,
            'days_of_week' => $days,
            'start_time' => $subscription->time_from,
            'end_time' => $subscription->time_to,
            'max_participants' => $maxParticipants,
        ]);

        return $subscription;
    }
}
