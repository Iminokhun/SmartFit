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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->decimal('price', 14, 2)->change();
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->decimal('salary', 14, 2)->change();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->decimal('amount', 14, 2)->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('amount', 14, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
