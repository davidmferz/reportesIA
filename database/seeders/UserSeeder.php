<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario administrador
        User::create([
            'name' => 'David Melchor',
            'email' => 'david.melchor@blmovil.com',
            'password' => Hash::make('localhost'),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->command->info('Usuario administrador creado exitosamente!');
        $this->command->info('Email: david.melchor@blmovil.com');
        $this->command->info('Password: localhost');
    }
}
