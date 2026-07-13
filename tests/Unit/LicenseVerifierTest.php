<?php

namespace Tests\Unit;

use App\Exceptions\LicenseInvalidException;
use App\Services\LicenseVerifier;
use Tests\Concerns\MakesLicenseTokens;
use Tests\TestCase;

class LicenseVerifierTest extends TestCase
{
    use MakesLicenseTokens;

    private LicenseVerifier $verifier;

    /** @var array{kid:string, public:string, secret:string} */
    private array $kp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kp = $this->licenseKeypair('k1');
        $this->configureLicenseKey('k1', $this->kp['public'], 'localhost');
        $this->verifier = new LicenseVerifier();
    }

    public function test_acepta_un_jws_eddsa_valido(): void
    {
        $token = $this->makeToken($this->defaultClaims(), $this->kp['secret'], 'k1');

        $claims = $this->verifier->verify($token);

        $this->assertSame('reportesia', $claims['client_id']);
        $this->assertSame(5, (int) $claims['max_users']);
    }

    public function test_rechaza_token_manipulado(): void
    {
        $token = $this->makeToken($this->defaultClaims(), $this->kp['secret'], 'k1');
        $tampered = substr($token, 0, -3) . 'AAA';

        $this->expectException(LicenseInvalidException::class);
        $this->verifier->verify($tampered);
    }

    public function test_rechaza_firma_con_clave_incorrecta(): void
    {
        $otro = $this->licenseKeypair('k1'); // mismo kid, otra clave privada
        $token = $this->makeToken($this->defaultClaims(), $otro['secret'], 'k1');

        $this->expectException(LicenseInvalidException::class);
        $this->verifier->verify($token);
    }

    public function test_rechaza_token_malformado(): void
    {
        $this->expectException(LicenseInvalidException::class);
        $this->verifier->verify('no-soy-un-jwt');
    }

    public function test_rechaza_alg_none(): void
    {
        $token = $this->makeUnsignedToken($this->defaultClaims(), 'k1');

        $this->expectException(LicenseInvalidException::class);
        $this->verifier->verify($token);
    }

    public function test_rechaza_confusion_de_algoritmo_hs256(): void
    {
        // Ataque: firma HS256 usando la clave PÚBLICA como secreto HMAC.
        $token = $this->makeToken($this->defaultClaims(), $this->kp['public'], 'k1', 'HS256');

        $this->expectException(LicenseInvalidException::class);
        $this->verifier->verify($token);
    }

    public function test_rechaza_kid_desconocido(): void
    {
        $token = $this->makeToken($this->defaultClaims(['kid' => 'k9']), $this->kp['secret'], 'k9');

        $this->expectException(LicenseInvalidException::class);
        $this->verifier->verify($token);
    }

    public function test_rechaza_si_falta_un_claim_requerido(): void
    {
        $claims = $this->defaultClaims();
        unset($claims['max_users']);
        $token = $this->makeToken($claims, $this->kp['secret'], 'k1');

        $this->expectException(LicenseInvalidException::class);
        $this->verifier->verify($token);
    }

    public function test_rechaza_dominio_distinto(): void
    {
        $token = $this->makeToken($this->defaultClaims(['domain' => 'otra-empresa.com']), $this->kp['secret'], 'k1');

        $this->expectException(LicenseInvalidException::class);
        $this->verifier->verify($token);
    }
}
