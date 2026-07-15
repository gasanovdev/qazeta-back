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
        $users = [
            [
                'full_name' => 'Qazeta User',
                'email' => 'user@qazeta.app',
                'phone_number' => '+77010000001',
                'password' => 'rstm2026!',
                'role' => 'user',
            ],
            [
                'full_name' => 'Qazeta Admin',
                'email' => 'admin@qazeta.app',
                'phone_number' => '+77010000002',
                'password' => 'rstm2026!',
                'role' => 'admin',
            ],
            [
                'full_name' => 'Qazeta Branch',
                'email' => 'branch@qazeta.app',
                'phone_number' => '+77010000003',
                'password' => 'rstm2026!',
                'role' => 'branch',
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'full_name' => $data['full_name'],
                    'phone_number' => $data['phone_number'],
                    'password' => $data['password'],
                    'role' => $data['role'],
                    'interest_ids' => [],
                ],
            );
        }
    }
}
