<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Budi Santoso',
                'email' => 'budi@otto.co.id',
            ],
            [
                'name' => 'Siti Aminah',
                'email' => 'siti.admin@otto.co.id',
            ],
            [
                'name' => 'Agus Pratama',
                'email' => 'agus.tek@otto.co.id',
            ],
        ];

        foreach ($users as $userData) {
            User::query()->updateOrCreate(
                ['email' => $userData['email']],
                [
                    ...$userData,
                    'password' => Hash::make('password123'),
                ]
            );
        }
    }
}
