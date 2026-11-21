<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'employee1@example.com'],
            [
                'name' => 'Employee One',
                'password' => Hash::make('Password@123'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'employee2@example.com'],
            [
                'name' => 'Employee Two',
                'password' => Hash::make('Password@123'),
            ]
        );
    }
}