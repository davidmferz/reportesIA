<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseBlockedScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_no_admin_autenticado_ve_la_pantalla_de_contacto(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'is_active' => true]);

        $this->actingAs($user)
            ->get(route('license.blocked.show'))
            ->assertOk()
            ->assertSee('administrador', false);
    }

    public function test_un_admin_tambien_puede_verla(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $this->actingAs($admin)
            ->get(route('license.blocked.show'))
            ->assertOk();
    }

    public function test_un_invitado_no_puede_verla(): void
    {
        $this->get(route('license.blocked.show'))->assertRedirect(route('login'));
    }
}
