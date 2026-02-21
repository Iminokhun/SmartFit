<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->string('item_type')->default('consumable')->after('category_id');
            $table->string('unit')->nullable()->after('quantity');
            $table->unsignedInteger('min_quantity')->default(0)->after('unit');
            $table->decimal('cost_price', 14, 2)->nullable()->after('min_quantity');
            $table->decimal('sell_price', 14, 2)->nullable()->after('cost_price');

            $table->string('asset_tag')->nullable()->after('sell_price');
            $table->string('serial_number')->nullable()->after('asset_tag');
            $table->string('condition')->nullable()->after('serial_number');
            $table->string('location')->nullable()->after('condition');
            $table->date('purchase_date')->nullable()->after('location');
            $table->decimal('purchase_price', 14, 2)->nullable()->after('purchase_date');
            $table->date('warranty_until')->nullable()->after('purchase_price');

            $table->index('item_type');
        });
    }

    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropIndex(['item_type']);

            $table->dropColumn([
                'item_type',
                'unit',
                'min_quantity',
                'cost_price',
                'sell_price',
                'asset_tag',
                'serial_number',
                'condition',
                'location',
                'purchase_date',
                'purchase_price',
                'warranty_until',
            ]);
        });
    }
};

