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
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'payments_status_created_at_idx');
            $table->index(['customer_subscription_id', 'status', 'created_at'], 'payments_sub_status_created_at_idx');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->index(['expenses_date'], 'expenses_date_idx');
        });

        Schema::table('customer_subscriptions', function (Blueprint $table) {
            $table->index(
                ['subscription_id', 'status', 'start_date', 'end_date'],
                'customer_subscriptions_sub_status_dates_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_status_created_at_idx');
            $table->dropIndex('payments_sub_status_created_at_idx');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('expenses_date_idx');
        });

        Schema::table('customer_subscriptions', function (Blueprint $table) {
            $table->dropIndex('customer_subscriptions_sub_status_dates_idx');
        });
    }
};
