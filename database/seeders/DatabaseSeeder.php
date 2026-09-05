<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        // A known default admin/password only ever gets seeded outside production.
        // Production admin accounts must be created via `php artisan admin:create`.
        if (app()->environment(['local', 'testing'])) {
            $admin = User::factory()->create([
                'name' => 'مدیر سیستم',
                'email' => 'admin@academy.test',
                'password' => 'password',
            ]);

            $admin->assignRole(RoleName::Admin->value);
        }
    }
}
