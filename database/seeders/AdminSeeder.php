<?php

namespace TrackAnyDevice\Core\Database\Seeders;

use TrackAnyDevice\Core\Enums\Role;
use TrackAnyDevice\Core\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $phone = env('ADMIN_PHONE');

        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@fleet.local')],
            [
                'name' => env('ADMIN_NAME', 'Fleet Admin'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'changeme')),
                'role' => Role::Admin,
                'email_verified_at' => now(),
                'primary_contact' => $phone ?: null,
                'phone_verified_at' => $phone ? now() : null,
            ]
        );
    }
}
