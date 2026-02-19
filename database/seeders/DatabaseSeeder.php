<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            FakeErpDataSeeder::class,
        ]);

//        $adminRole = Role::firstOrCreate(['name' => 'admin']);
//
//        User::updateOrCreate(
//            ['email' => 'test@example.com'],
//            [
//                'name' => 'Test User',
//                'password' => bcrypt('password'),
//                'role_id' => $adminRole->id,
//            ]
//        );
    }
}
