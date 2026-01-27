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
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('day_of_week');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->json('days_of_week');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('days_of_week)');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->enum('day_of_week', [
                'monday','tuesday','wednesday',
                'thursday','friday','saturday','sunday'
            ]);
        });
    }
};
