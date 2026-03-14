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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->json('allowed_weekdays')->nullable()->after('activity_id');
            $table->time('time_from')->nullable()->after('allowed_weekdays');
            $table->time('time_to')->nullable()->after('time_from');
            $table->unsignedTinyInteger('max_checkins_per_day')->nullable()->after('time_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'allowed_weekdays',
                'time_from',
                'time_to',
                'max_checkins_per_day',
            ]);
        });
    }
};
