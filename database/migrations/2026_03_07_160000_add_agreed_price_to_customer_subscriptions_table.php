<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_subscriptions', function (Blueprint $table) {
            $table->decimal('agreed_price', 10, 2)->nullable()->after('subscription_id');
        });

        $subscriptionMap = DB::table('subscriptions')
            ->select('id', 'price', 'discount')
            ->get()
            ->keyBy('id');

        $hasPaidPaymentIds = DB::table('payments')
            ->where('status', 'paid')
            ->whereNotNull('customer_subscription_id')
            ->pluck('customer_subscription_id')
            ->flip();

        DB::table('customer_subscriptions')
            ->select('id', 'subscription_id', 'paid_amount', 'payment_status')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($subscriptionMap, $hasPaidPaymentIds): void {
                foreach ($rows as $row) {
                    $subscription = $subscriptionMap->get($row->subscription_id);
                    $basePrice = (float) ($subscription->price ?? 0);
                    $discount = (float) ($subscription->discount ?? 0);
                    $templateFinal = max(0, round($basePrice - ($basePrice * $discount / 100), 2));

                    $paidAmount = round((float) ($row->paid_amount ?? 0), 2);
                    $hasPaidPayment = $hasPaidPaymentIds->has((int) $row->id);

                    $agreedPrice = $templateFinal;
                    if ($hasPaidPayment && $paidAmount > 0 && $paidAmount < $templateFinal) {
                        $agreedPrice = $paidAmount;
                    }

                    $debt = max(0, round($agreedPrice - $paidAmount, 2));
                    $paymentStatus = $agreedPrice <= 0
                        ? 'paid'
                        : ($paidAmount >= $agreedPrice ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid'));

                    DB::table('customer_subscriptions')
                        ->where('id', (int) $row->id)
                        ->update([
                            'agreed_price' => $agreedPrice,
                            'debt' => $debt,
                            'payment_status' => $paymentStatus,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('customer_subscriptions', function (Blueprint $table) {
            $table->dropColumn('agreed_price');
        });
    }
};

