<?php

namespace Tests\Unit;

use App\Enums\LicenseStatus;
use App\Models\LicenseState;
use App\Services\LicenseVerifier;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Rollback de reloj: si el sistema está antes de la última validación
 * exitosa (`last_seen_at`), no se puede confiar en el tiempo local para
 * decidir Valid/Grace, así que se fuerza el bloqueo (y, por extensión, la
 * revalidación online vía license:check) en vez de dejar pasar en silencio.
 */
class LicenseRollbackTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function verifier(): LicenseVerifier
    {
        return new LicenseVerifier();
    }

    private function state(array $attrs = []): LicenseState
    {
        return new LicenseState(array_merge([
            'raw_token' => 'a.b.c',
            'payload' => ['kid' => 'k1'],
            'valid_from' => '2026-01-01 00:00:00',
            'valid_until' => '2026-12-31 23:59:59',
            'max_users' => 5,
        ], $attrs));
    }

    public function test_reloj_atrasado_respecto_de_last_seen_bloquea_aunque_la_fecha_de_vigencia_sea_valida(): void
    {
        // El reloj retrocedió a antes de la última validación exitosa: no
        // hay que confiar en que "estamos dentro de valid_until" localmente.
        Carbon::setTestNow('2026-06-01 00:00:00');

        $state = $this->state(['last_seen_at' => '2026-06-10 00:00:00']);

        $this->assertSame(LicenseStatus::Blocked, $this->verifier()->resolveStatus($state));
    }

    public function test_reloj_atrasado_no_otorga_silenciosamente_gracia(): void
    {
        // Sin la detección de rollback, esta fecha caería en la ventana de
        // gracia (vencida hace 5 días, grace_days=14). Con el reloj antes de
        // last_seen_at, el rollback tiene que ganarle a esa cuenta y bloquear.
        Carbon::setTestNow('2027-01-05 00:00:00');

        $state = $this->state(['last_seen_at' => '2027-06-01 00:00:00']);

        $this->assertSame(LicenseStatus::Blocked, $this->verifier()->resolveStatus($state));
    }

    public function test_reloj_igual_a_last_seen_no_es_rollback(): void
    {
        // now() == last_seen_at no es "antes de": no debe bloquear por rollback.
        Carbon::setTestNow('2026-06-10 00:00:00');

        $state = $this->state(['last_seen_at' => '2026-06-10 00:00:00']);

        $this->assertSame(LicenseStatus::Valid, $this->verifier()->resolveStatus($state));
    }

    public function test_sin_last_seen_registrado_no_hay_chequeo_de_rollback(): void
    {
        // Licencia recién activada, todavía sin una validación online
        // registrada: no hay nada contra qué comparar, así que la fecha de
        // vigencia local decide con normalidad.
        Carbon::setTestNow('2026-06-01 00:00:00');

        $state = $this->state(['last_seen_at' => null]);

        $this->assertSame(LicenseStatus::Valid, $this->verifier()->resolveStatus($state));
    }
}
