<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        $users = [
            [
                'name' => 'Admin',
                'email' => 'admin@nefrotrack.com',
                'password' => Hash::make('password'),
                'role_id' => 1
            ],
            [
                'name' => 'Coordinador',
                'email' => 'coordinador@nefrotrack.com',
                'password' => Hash::make('password'),
                'role_id' => 2
            ],
            [
                'name' => 'Asistente',
                'email' => 'asistente@nefrotrack.com',
                'password' => Hash::make('password'),
                'role_id' => 3
            ]
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}