<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('telegram_user_id')->unique();
            $table->string('telegram_username')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('linked_at')->nullable();
            $table->timestamps();

            $table->unique('customer_id');
            $table->index(['is_verified', 'linked_at'], 'telegram_links_verified_linked_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_links');
    }
};

