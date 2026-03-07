<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE customer_subscriptions DROP CONSTRAINT IF EXISTS customer_subscriptions_status_check');
            DB::statement("ALTER TABLE customer_subscriptions ADD CONSTRAINT customer_subscriptions_status_check CHECK (status IN ('active', 'pending', 'expired', 'frozen', 'cancelled'))");

            return;
        }

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE customer_subscriptions MODIFY status ENUM('active','pending','expired','frozen','cancelled') NOT NULL DEFAULT 'active'");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE customer_subscriptions DROP CONSTRAINT IF EXISTS customer_subscriptions_status_check');
            DB::statement("ALTER TABLE customer_subscriptions ADD CONSTRAINT customer_subscriptions_status_check CHECK (status IN ('active', 'expired', 'frozen', 'cancelled'))");

            return;
        }

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE customer_subscriptions MODIFY status ENUM('active','expired','frozen','cancelled') NOT NULL DEFAULT 'active'");
        }
    }
};

