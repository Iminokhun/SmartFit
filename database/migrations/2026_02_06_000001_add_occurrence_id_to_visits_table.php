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
            $table->foreignId('occurrence_id')
                ->nullable()
                ->after('schedule_id')
                ->constrained('schedule_occurrences')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropForeign(['occurrence_id']);
            $table->dropColumn('occurrence_id');
        });
    }
};

