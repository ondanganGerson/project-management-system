<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ── 3 Admins ──────────────────────────────────────────────────────
        foreach (range(1, 3) as $i) {
            User::create([
                'name'              => "Admin User {$i}",
                'email'             => "admin{$i}@pms.local",
                'password'          => Hash::make('password'),
                'phone'             => "+1-555-000-{$i}00{$i}",
                'role'              => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ]);
        }

        // ── 3 Managers ────────────────────────────────────────────────────
        foreach (range(1, 3) as $i) {
            User::create([
                'name'              => "Manager User {$i}",
                'email'             => "manager{$i}@pms.local",
                'password'          => Hash::make('password'),
                'phone'             => "+1-555-001-{$i}00{$i}",
                'role'              => User::ROLE_MANAGER,
                'email_verified_at' => now(),
            ]);
        }

        // ── 5 Regular Users ───────────────────────────────────────────────
        foreach (range(1, 5) as $i) {
            User::create([
                'name'              => "Regular User {$i}",
                'email'             => "user{$i}@pms.local",
                'password'          => Hash::make('password'),
                'phone'             => "+1-555-002-{$i}00{$i}",
                'role'              => User::ROLE_USER,
                'email_verified_at' => now(),
            ]);
        }
    }
}
