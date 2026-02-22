<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('panel', 32)->nullable();
            $table->string('guard', 64)->nullable();
            $table->string('status', 16);
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['panel', 'status', 'created_at'], 'auth_logs_panel_status_created_at_idx');
            $table->index(['user_id', 'created_at'], 'auth_logs_user_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_logs');
    }
};

