<?php

namespace Tests\Feature;

use App\Models\LicenseState;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesLicenseTokens;
use Tests\TestCase;

class LicenseControllerTest extends TestCase
{
    use MakesLicenseTokens;
    use RefreshDatabase;

    /** @var array{kid:string, public:string, secret:string} */
    private array $kp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kp = $this->licenseKeypair('k1');
        $this->configureLicenseKey('k1', $this->kp['public'], 'localhost');
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'is_active' => true]);
    }

    private function regular(): User
    {
        return User::factory()->create(['is_admin' => false, 'is_active' => true]);
    }

    public function test_la_activacion_requiere_login(): void
    {
        $this->get(route('license.activation.show'))->assertRedirect(route('login'));
    }

    public function test_la_activacion_esta_prohibida_para_no_admin(): void
    {
        $this->actingAs($this->regular())
            ->get(route('license.activation.show'))
            ->assertForbidden();
    }

    public function test_el_admin_ve_el_formulario_de_activacion(): void
    {
        $this->actingAs($this->admin())
            ->get(route('license.activation.show'))
            ->assertOk()
            ->assertSee('Activación de licencia', false);
    }

    public function test_store_con_token_valido_activa_la_licencia(): void
    {
        $admin = $this->admin();
        $token = $this->makeToken($this->defaultClaims(), $this->kp['secret'], 'k1');

        $this->actingAs($admin)
            ->post(route('license.activation.store'), ['token' => $token])
            ->assertRedirect(route('license.activation.show'))
            ->assertSessionHas('status', 'license-activated');

        $this->assertNotNull(LicenseState::current());
        $this->assertSame($admin->id, LicenseState::current()->activated_by);
    }

    public function test_store_con_token_invalido_no_cambia_nada(): void
    {
        $this->actingAs($this->admin())
            ->post(route('license.activation.store'), ['token' => 'basura'])
            ->assertSessionHasErrors('token');

        $this->assertNull(LicenseState::current());
    }
}
