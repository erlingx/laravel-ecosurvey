<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@admin.com',
            'password' => config('seeding.passwords.admin'),
            'email_verified_at' => now(),
            'two_factor_confirmed_at' => null,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ]);
        User::factory()->create([
            'name' => 'dev',
            'email' => 'dev@dev.com',
            'password' => config('seeding.passwords.dev'),
            'email_verified_at' => now(),
            'two_factor_confirmed_at' => null,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ]);
        User::factory()->create([
            'name' => 'user',
            'email' => 'user@user.com',
            'password' => config('seeding.passwords.user'),
            'email_verified_at' => now(),
            'two_factor_confirmed_at' => null,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ]);
    }
}
