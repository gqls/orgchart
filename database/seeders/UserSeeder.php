<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create an admin user with a predictable password for testing
        DB::table('users')->insert([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'), // Using a simple password for development
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create some regular users
        $regularUsers = [
            [
                'name' => 'John Demo',
                'email' => 'john@example.com',
            ],
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah@example.com',
            ],
            [
                'name' => 'Michael Chen',
                'email' => 'michael@example.com',
            ],
            [
                'name' => 'Emily Rodriguez',
                'email' => 'emily@example.com',
            ],
        ];

        foreach ($regularUsers as $user) {
            DB::table('users')->insert([
                'name' => $user['name'],
                'email' => $user['email'],
                'password' => Hash::make('password'), // Same password for all test users
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}