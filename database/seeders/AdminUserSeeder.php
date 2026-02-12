<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admins = [
            [
                'name' => 'Linsey',
                'email' => 'linsey@kapr.nl',
                'password' => 'Rookworst2026!',
            ],
            [
                'name' => 'Sebastiaan',
                'email' => 'sebastiaan36@gmail.com',
                'password' => 'Code1code',
            ],
            [
                'name' => 'Louman Jordaan',
                'email' => 'info@louman-jordaan.nl',
                'password' => 'Rookworst2026!',
            ],
        ];

        foreach ($admins as $admin) {
            User::firstOrCreate(
                ['email' => $admin['email']],
                [
                    'name' => $admin['name'],
                    'password' => Hash::make($admin['password']),
                    'role' => 'admin',
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
