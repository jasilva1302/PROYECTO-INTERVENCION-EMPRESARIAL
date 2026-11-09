<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test users for each role (except admin)
        $testUsers = [
            [
                'name' => 'Usuario Auditor',
                'email' => 'auditor@nefrotrack.com',
                'password' => Hash::make('auditor123'),
                'role' => 'Auditor'
            ],
            [
                'name' => 'Usuario Asistente',
                'email' => 'asistente@nefrotrack.com',
                'password' => Hash::make('asistente123'),
                'role' => 'Asistente'
            ],
            [
                'name' => 'Usuario Facturación',
                'email' => 'facturacion@nefrotrack.com',
                'password' => Hash::make('facturacion123'),
                'role' => 'Facturación'
            ],
            [
                'name' => 'Usuario Consulta',
                'email' => 'consulta@nefrotrack.com',
                'password' => Hash::make('consulta123'),
                'role' => 'Consulta'
            ]
        ];

        foreach ($testUsers as $userData) {
            $role = Role::where('name', $userData['role'])->first();
            
            if ($role) {
                $user = User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => $userData['password']
                ]);

                $user->roles()->attach($role->id);
            }
        }
    }
}
