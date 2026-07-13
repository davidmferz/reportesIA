<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureLicenseIsValid;
use App\Models\ActivityLog;
use App\Models\LicenseState;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LicenseEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ruta protegida ad-hoc (el cableado real a los grupos es Batch 3.7,
        // diferido hasta que existan las pantallas de Batch 5).
        Route::middleware(['web', 'auth', EnsureLicenseIsValid::class])
            ->get('/__enforced', fn () => 'contenido')->name('__enforced');

        // Destinos de bloqueo (stubs; las vistas reales son Batch 5).
        Route::middleware('web')->get('/__activation', fn () => 'activar')->name('license.activation.show');
        Route::middleware('web')->get('/__blocked', fn () => 'bloqueado')->name('license.blocked.show');

        // Los nombres fluidos (->name()) agregados en runtime no entran al índice
        // de nombres hasta refrescarlo explícitamente.
        Route::getRoutes()->refreshNameLookups();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function user(bool $admin = true): User
    {
        return User::factory()->create(['is_admin' => $admin, 'is_active' => true]);
    }

    private function license(array $attrs = []): LicenseState
    {
        return LicenseState::create(array_merge([
            'raw_token' => 'a.b.c',
            'payload' => ['kid' => 'k1'],
            'valid_from' => '2026-01-01 00:00:00',
            'valid_until' => '2027-01-01 00:00:00',
            'max_users' => 5,
        ], $attrs));
    }

    public function test_licencia_valida_deja_pasar(): void
    {
        Carbon::setTestNow('2026-06-01 00:00:00');
        $this->license();

        $this->actingAs($this->user())->get('/__enforced')
            ->assertOk()
            ->assertSee('contenido');
    }

    public function test_en_gracia_deja_pasar_y_comparte_el_banner(): void
    {
        Carbon::setTestNow('2027-01-05 00:00:00'); // 5 días tras valid_until, grace 14
        $this->license();

        $this->actingAs($this->user())->get('/__enforced')->assertOk();

        $this->assertNotNull(view()->shared('licenseGraceUntil'));
    }

    public function test_gracia_agotada_redirige_admin_a_activacion(): void
    {
        Carbon::setTestNow('2027-02-01 00:00:00'); // >14 días tras valid_until
        $this->license();

        $this->actingAs($this->user(admin: true))->get('/__enforced')
            ->assertRedirect(route('license.activation.show'));
    }

    public function test_no_admin_bloqueado_va_a_pantalla_de_contacto_no_403(): void
    {
        Carbon::setTestNow('2027-02-01 00:00:00');
        $this->license();

        $this->actingAs($this->user(admin: false))->get('/__enforced')
            ->assertRedirect(route('license.blocked.show'));
    }

    public function test_sin_licencia_redirige_a_activacion(): void
    {
        $this->actingAs($this->user(admin: true))->get('/__enforced')
            ->assertRedirect(route('license.activation.show'));
    }

    public function test_transicion_a_bloqueo_se_audita_una_sola_vez(): void
    {
        Carbon::setTestNow('2027-02-01 00:00:00');
        $this->license(['enforcement_status' => 'grace']); // venía de gracia
        $admin = $this->user();

        $this->actingAs($admin)->get('/__enforced')->assertRedirect(route('license.activation.show'));
        $this->actingAs($admin)->get('/__enforced')->assertRedirect(route('license.activation.show'));

        $this->assertSame(
            1,
            ActivityLog::where('log_name', 'LicenseState')->where('event', 'license_transition')->count()
        );
    }
}
