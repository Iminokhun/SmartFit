<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_staff_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('telegram_user_id')->unique();
            $table->string('telegram_username')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->boolean('is_verified')->default(true);
            $table->timestamp('linked_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['is_verified', 'linked_at'], 'telegram_staff_links_verified_linked_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_staff_links');
    }
};

