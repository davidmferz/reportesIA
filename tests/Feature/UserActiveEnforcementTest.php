<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureUserIsActive;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class UserActiveEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', EnsureUserIsActive::class])
            ->get('/__active', fn () => 'contenido')->name('__active');
    }

    public function test_usuario_activo_no_se_ve_afectado(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->get('/__active')
            ->assertOk()
            ->assertSee('contenido');
    }

    public function test_usuario_desactivado_es_expulsado_de_inmediato(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($user)->get('/__active')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
