<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Сначала создаём новую колонку hall_id (временно nullable)
        Schema::table('schedules', function (Blueprint $table) {
            $table->foreignId('hall_id')
                ->nullable()
                ->after('trainer_id')
                ->constrained('halls')
                ->nullOnDelete();
        });

        // Переносим данные из hall в hall_id
        // Создаём Hall записи для каждого уникального значения hall
        $uniqueHalls = \DB::table('schedules')
            ->select('hall')
            ->distinct()
            ->whereNotNull('hall')
            ->pluck('hall');

        foreach ($uniqueHalls as $hallName) {
            $hallId = \DB::table('halls')->insertGetId([
                'name' => $hallName,
                'capacity' => null,
                'description' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Обновляем schedules с этим hall именем
            \DB::table('schedules')
                ->where('hall', $hallName)
                ->update(['hall_id' => $hallId]);
        }

        // Теперь удаляем старую колонку hall
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('hall');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->string('hall')->nullable()->after('trainer_id');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropForeign(['hall_id']);
            $table->dropColumn('hall_id');
        });
    }
};
