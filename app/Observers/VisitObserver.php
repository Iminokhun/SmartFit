<?php

namespace App\Observers;

use App\Models\Visit;


use App\Services\SubsriptionSevsice;
class VisitObserver
{
    /**
     * Handle the Visit "created" event.
     */
//    public function created(Visit $visit): void
//    {
//        if ($visit->status !== 'visited') {
//            return;
//        }
//
//        app(SubscriptionService::class)
//            ->decrementVisit($visit->customer);
//
//    }

    /**
     * Handle the Visit "updated" event.
     */
    public function updated(Visit $visit): void
    {
        //
    }

    /**
     * Handle the Visit "deleted" event.
     */
    public function deleted(Visit $visit): void
    {
        //
    }

    /**
     * Handle the Visit "restored" event.
     */
    public function restored(Visit $visit): void
    {
        //
    }

    /**
     * Handle the Visit "force deleted" event.
     */
    public function forceDeleted(Visit $visit): void
    {
        //
    }
}
