<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\LicenseState;
use App\Models\User;
use App\Services\LicenseVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\Concerns\MakesLicenseTokens;
use Tests\TestCase;

class LicenseCheckCommandTest extends TestCase
{
    use MakesLicenseTokens;
    use RefreshDatabase;

    /** @var array{kid:string, public:string, secret:string} */
    private array $kp;

    protected function setUp(): void
    {
        parent::setUp();

        Sleep::fake();

        $this->kp = $this->licenseKeypair('k1');
        $this->configureLicenseKey('k1', $this->kp['public'], 'localhost');
        config([
            'license.server_url' => 'https://licensing.test',
            'license.client_id' => 'reportesia',
        ]);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'secret',
            'is_admin' => true,
        ]);
    }

    private function activateInitialLicense(): LicenseState
    {
        $token = $this->makeToken($this->defaultClaims(), $this->kp['secret'], 'k1');

        return (new LicenseVerifier())->activate($token, $this->admin());
    }

    public function test_token_renovado_reemplaza_el_estado_persistido(): void
    {
        $state = $this->activateInitialLicense();

        $renewedToken = $this->makeToken(
            $this->defaultClaims(['valid_until' => '2028-01-01T00:00:00+00:00', 'max_users' => 9]),
            $this->kp['secret'],
            'k1'
        );

        Http::fake([
            'licensing.test/api/v1/licenses/reportesia/validate' => Http::response([
                'status' => 'active',
                'token' => $renewedToken,
                'reason' => null,
            ]),
        ]);

        $this->artisan('license:check')->assertSuccessful();

        $fresh = LicenseState::current();
        $this->assertSame($state->id, $fresh->id);
        $this->assertSame($renewedToken, $fresh->raw_token);
        $this->assertSame(9, $fresh->max_users);
        $this->assertNotNull($fresh->last_seen_at);
    }

    public function test_respuesta_revoked_invalida_la_licencia_local_y_audita(): void
    {
        $this->activateInitialLicense();

        Http::fake([
            'licensing.test/api/v1/licenses/reportesia/validate' => Http::response([
                'status' => 'revoked',
                'token' => null,
                'reason' => 'contrato vencido',
            ]),
        ]);

        $this->artisan('license:check')->assertSuccessful();

        $fresh = LicenseState::current();
        $this->assertSame('revoked', $fresh->last_check_result);

        $this->assertTrue(
            ActivityLog::where('log_name', 'LicenseState')
                ->where('event', 'updated')
                ->exists(),
            'La revocación tiene que auditarse.'
        );
    }

    public function test_servidor_inalcanzable_deja_el_estado_local_sin_cambios(): void
    {
        $this->activateInitialLicense();
        $before = LicenseState::current()->toArray();

        Http::fake(function () {
            throw new ConnectionException('No se pudo conectar al servidor de licencias.');
        });

        $this->artisan('license:check')->assertSuccessful();

        $after = LicenseState::current()->fresh()->toArray();

        $this->assertSame($before, $after);
    }
}
