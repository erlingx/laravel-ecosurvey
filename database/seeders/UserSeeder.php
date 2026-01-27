<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Use updateOrCreate to prevent duplicate key errors
        User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'admin',
                'password' => Hash::make(config('seeding.passwords.admin')),
                'email_verified_at' => now(),
                'two_factor_confirmed_at' => null,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
            ]
        );

        User::updateOrCreate(
            ['email' => 'dev@dev.com'],
            [
                'name' => 'dev',
                'password' => Hash::make(config('seeding.passwords.dev')),
                'email_verified_at' => now(),
                'two_factor_confirmed_at' => null,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
            ]
        );

        User::updateOrCreate(
            ['email' => 'user@user.com'],
            [
                'name' => 'user',
                'password' => Hash::make(config('seeding.passwords.user')),
                'email_verified_at' => now(),
                'two_factor_confirmed_at' => null,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
            ]
        );
    }
}
