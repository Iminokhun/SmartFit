<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checkin_token_id')->nullable()->constrained('checkin_tokens')->nullOnDelete();
            $table->foreignId('checked_in_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_in_at');
            $table->timestamps();

            $table->unique('checkin_token_id');
            $table->index(['customer_id', 'checked_in_at'], 'customer_checkins_customer_time_idx');
            $table->index(['customer_subscription_id', 'checked_in_at'], 'customer_checkins_subscription_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_checkins');
    }
};

