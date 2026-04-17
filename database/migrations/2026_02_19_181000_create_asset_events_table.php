<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventories')->cascadeOnDelete();
            $table->string('event_type');
            $table->dateTime('event_date');
            $table->foreignId('from_hall_id')->nullable()->constrained('halls')->nullOnDelete();
            $table->foreignId('to_hall_id')->nullable()->constrained('halls')->nullOnDelete();
            $table->string('condition_before')->nullable();
            $table->string('condition_after')->nullable();
            $table->unsignedTinyInteger('status_before')->nullable();
            $table->unsignedTinyInteger('status_after')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['inventory_id', 'event_date']);
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_events');
    }
};
