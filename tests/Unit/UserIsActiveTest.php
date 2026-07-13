<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserIsActiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_active_por_defecto_es_true(): void
    {
        $user = User::create([
            'name' => 'Nuevo',
            'email' => 'nuevo@example.com',
            'password' => 'secret',
        ]);

        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_scope_active_excluye_inactivos(): void
    {
        $activo = User::create([
            'name' => 'Activo',
            'email' => 'activo@example.com',
            'password' => 'secret',
        ]);

        $inactivo = User::create([
            'name' => 'Inactivo',
            'email' => 'inactivo@example.com',
            'password' => 'secret',
            'is_active' => false,
        ]);

        $ids = User::active()->pluck('id');

        $this->assertTrue($ids->contains($activo->id));
        $this->assertFalse($ids->contains($inactivo->id));
    }
}
