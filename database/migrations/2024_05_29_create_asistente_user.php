<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primero obtenemos o creamos el rol ASISTENTE
        $roleAsistente = Role::firstOrCreate(
            ['name' => 'ASISTENTE'],
            ['description' => 'Asistente con acceso limitado y participación en evaluaciones']
        );

        // Creamos el usuario asistente
        User::create([
            'name' => 'Asistente',
            'email' => 'asistente@gmail.com',
            'password' => Hash::make('asistente'),
            'role_id' => $roleAsistente->id
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminamos el usuario asistente si existe
        User::where('email', 'asistente@gmail.com')->delete();
    }
}; 