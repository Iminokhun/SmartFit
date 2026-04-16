<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('telegram_payment_charge_id', 120)
                ->nullable()
                ->after('description');

            $table->string('provider_payment_charge_id', 120)
                ->nullable()
                ->after('telegram_payment_charge_id');

            $table->unique('telegram_payment_charge_id', 'payments_telegram_charge_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('payments_telegram_charge_unique');
            $table->dropColumn(['telegram_payment_charge_id', 'provider_payment_charge_id']);
        });
    }
};

