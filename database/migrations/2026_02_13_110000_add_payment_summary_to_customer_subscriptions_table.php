<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customer_subscriptions', function (Blueprint $table) {
            $table->decimal('paid_amount', 10, 2)
                ->default(0)
                ->after('remaining_visits');

            $table->decimal('debt', 10, 2)
                ->default(0)
                ->after('paid_amount');

            $table->string('payment_status', 20)
                ->default('unpaid')
                ->after('debt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'debt', 'payment_status']);
        });
    }
};
