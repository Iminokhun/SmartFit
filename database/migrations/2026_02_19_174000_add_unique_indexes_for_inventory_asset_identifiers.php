<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->unique(['item_type', 'serial_number'], 'inventories_item_type_serial_unique');
            $table->unique(['item_type', 'asset_tag'], 'inventories_item_type_asset_tag_unique');
        });
    }

    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropUnique('inventories_item_type_serial_unique');
            $table->dropUnique('inventories_item_type_asset_tag_unique');
        });
    }
};
