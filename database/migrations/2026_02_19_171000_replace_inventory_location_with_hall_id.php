<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->foreignId('hall_id')->nullable()->after('condition')->constrained('halls')->nullOnDelete();
        });

        if (Schema::hasColumn('inventories', 'location')) {
            Schema::table('inventories', function (Blueprint $table) {
                $table->dropColumn('location');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('inventories', 'hall_id')) {
            Schema::table('inventories', function (Blueprint $table) {
                $table->dropConstrainedForeignId('hall_id');
            });
        }

        Schema::table('inventories', function (Blueprint $table) {
            $table->string('location')->nullable()->after('condition');
        });
    }
};
