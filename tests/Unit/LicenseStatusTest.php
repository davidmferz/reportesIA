<?php

namespace Tests\Unit;

use App\Enums\LicenseStatus;
use App\Models\LicenseState;
use App\Services\LicenseVerifier;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LicenseStatusTest extends TestCase
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

    public function test_sin_licencia_es_unlicensed(): void
    {
        $this->assertSame(LicenseStatus::Unlicensed, $this->verifier()->resolveStatus(null));
    }

    public function test_dentro_de_vigencia_es_valid(): void
    {
        Carbon::setTestNow('2026-06-01 00:00:00');

        $this->assertSame(LicenseStatus::Valid, $this->verifier()->resolveStatus($this->state()));
    }

    public function test_vencida_pero_dentro_de_gracia_es_grace(): void
    {
        // 5 días después de valid_until, con grace_days=14.
        Carbon::setTestNow('2027-01-05 00:00:00');

        $this->assertSame(LicenseStatus::Grace, $this->verifier()->resolveStatus($this->state()));
    }

    public function test_gracia_agotada_es_blocked(): void
    {
        // 20 días después de valid_until, con grace_days=14.
        Carbon::setTestNow('2027-01-20 00:00:00');

        $this->assertSame(LicenseStatus::Blocked, $this->verifier()->resolveStatus($this->state()));
    }

    public function test_reloj_atrasado_respecto_de_last_seen_es_blocked(): void
    {
        // Dentro de vigencia por fecha, pero el reloj está antes de la última
        // validación exitosa: no se puede confiar en el tiempo local.
        Carbon::setTestNow('2026-06-01 00:00:00');

        $state = $this->state(['last_seen_at' => '2026-06-10 00:00:00']);

        $this->assertSame(LicenseStatus::Blocked, $this->verifier()->resolveStatus($state));
    }

    public function test_revocada_es_blocked_aunque_las_fechas_sean_validas(): void
    {
        Carbon::setTestNow('2026-06-01 00:00:00');

        $state = $this->state(['last_check_result' => 'revoked']);

        $this->assertSame(LicenseStatus::Blocked, $this->verifier()->resolveStatus($state));
    }
}
