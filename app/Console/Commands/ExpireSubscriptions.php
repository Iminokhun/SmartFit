<?php

namespace App\Console\Commands;

use App\Models\CustomerSubscription;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Mark subscriptions as expired when end_date has passed or visits are exhausted';

    public function handle(): int
    {
        $today = Carbon::today()->toDateString();

        $expired = CustomerSubscription::query()
            ->where('status', 'active')
            ->where(function ($q) use ($today) {
                $q->where('end_date', '<', $today)
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('remaining_visits')
                            ->where('remaining_visits', '<=', 0);
                    });
            })
            ->update(['status' => 'expired']);

        $this->info("Expired {$expired} subscription(s).");

        return self::SUCCESS;
    }
}
