<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'role' => 'admin',
            ],
            [
                'name' => 'Buyer User',
                'email' => 'buyer@example.com',
                'role' => 'buyer',
            ],
            [
                'name' => 'Seller User',
                'email' => 'seller@example.com',
                'role' => 'seller',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'remember_token' => Str::random(10),
                    'role' => $user['role'],
                    'api_key' => Str::random(64),
                    'request_limit' => 1000,
                    'requests_used' => 0,
                    'subscription_valid_until' => now()->add('1 year'),
                    'is_active' => true,
                ]
            );
        }
    }
}
