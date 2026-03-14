<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_checkins', function (Blueprint $table) {
            $table->foreignId('schedule_id')->nullable()->after('customer_subscription_id')->constrained('schedules')->nullOnDelete();
            $table->index(['schedule_id', 'checked_in_at'], 'customer_checkins_schedule_time_idx');
        });
    }

    public function down(): void
    {
        Schema::table('customer_checkins', function (Blueprint $table) {
            $table->dropIndex('customer_checkins_schedule_time_idx');
            $table->dropConstrainedForeignId('schedule_id');
        });
    }
};
