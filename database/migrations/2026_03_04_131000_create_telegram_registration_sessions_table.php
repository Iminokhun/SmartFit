<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_registration_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('telegram_user_id')->unique();
            $table->unsignedBigInteger('chat_id');
            $table->string('step', 40)->default('awaiting_contact');
            $table->string('phone_normalized', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();

            $table->index(['step', 'updated_at'], 'telegram_reg_sessions_step_updated_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_registration_sessions');
    }
};

