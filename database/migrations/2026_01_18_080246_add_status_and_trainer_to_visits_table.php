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
        Schema::table('visits', function (Blueprint $table) {
            $table->enum('status', ['visited', 'missed', 'cancelled'])
                ->default('visited')
                ->after('visited_at');

            // тренер (staff)
            $table->foreignId('trainer_id')
                ->after('schedule_id')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropForeign(['trainer_id']);
            $table->dropColumn(['status', 'trainer_id']);
        });
    }
};
