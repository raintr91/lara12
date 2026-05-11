<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@mairy.local'],
            [
                'name' => 'Mairy Admin',
                'full_name' => 'Mairy Admin',
                'password' => 'Ad@123456',
                'active' => true,
                'status' => 1,
            ]
        );
    }
}
