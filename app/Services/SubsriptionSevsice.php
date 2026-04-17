<?php
//
//namespace App\Services;
//
//use App\Models\Customer;
//use Exception;
//class SubsriptionSevsice
//{
//    public function getActiveSubscription(Customer $customer)
//    {
//        return $customer->customerSubsciption()
//            ->where('status', 'active')
//            ->first();
//    }
//
//    public function assertHasRemainingVisits(Customer $customer): void
//    {
//        $subscription = $this->getActiveSubscription($customer);
//
//        if (! $subscription || $subscription->remaining_visits <= 0) {
//            throw new Exception('Customer has no remaining visits.');
//        }
//    }
//
//    public function decrementVisit(Customer $customer): void
//    {
//        $subscription = $this->getActiveSubscription($customer);
//
//        if ($subscription) {
//            $subscription->decrement('remaining_visits');
//        }
//    }
//}
