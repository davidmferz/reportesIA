<?php

namespace Tests\Feature;

use App\Exceptions\LicenseInvalidException;
use App\Models\ActivityLog;
use App\Models\LicenseState;
use App\Models\User;
use App\Services\LicenseVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesLicenseTokens;
use Tests\TestCase;

class LicenseActivationTest extends TestCase
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
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'secret',
            'is_admin' => true,
        ]);
    }

    public function test_un_token_valido_persiste_estado_y_audita(): void
    {
        $admin = $this->admin();
        $token = $this->makeToken($this->defaultClaims(), $this->kp['secret'], 'k1');

        $state = (new LicenseVerifier())->activate($token, $admin);

        $this->assertInstanceOf(LicenseState::class, $state);
        $this->assertSame($token, LicenseState::current()->raw_token);
        $this->assertSame(5, LicenseState::current()->max_users);
        $this->assertSame($admin->id, LicenseState::current()->activated_by);

        $this->assertNotNull(
            ActivityLog::where('log_name', 'LicenseState')->where('event', 'created')->first()
        );
    }

    public function test_un_token_invalido_no_persiste_nada(): void
    {
        $admin = $this->admin();

        try {
            (new LicenseVerifier())->activate('token-invalido', $admin);
            $this->fail('Se esperaba LicenseInvalidException.');
        } catch (LicenseInvalidException) {
            // esperado
        }

        $this->assertSame(0, LicenseState::count());
        $this->assertNull(LicenseState::current());
    }
}
