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
        User::firstOrCreate(
            ['email' => 'david.melchor@blmovil.com'],
            [
                'name' => 'David Melchor',
                'password' => Hash::make('localhost'),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Usuario administrador creado exitosamente');
        $this->command->info('Email: david.melchor@blmovil.com');
        $this->command->info('Password: localhost');
    }
}
