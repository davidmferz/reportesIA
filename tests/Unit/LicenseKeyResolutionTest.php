<?php

namespace Tests\Unit;

use App\Exceptions\LicenseInvalidException;
use App\Services\LicenseVerifier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesLicenseTokens;
use Tests\TestCase;

class LicenseKeyResolutionTest extends TestCase
{
    use MakesLicenseTokens;

    private function configureJwks(): void
    {
        config([
            'license.server_url' => 'https://licensing.test',
            'license.product_id' => 'reportesia',
            'app.url' => 'https://localhost',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function jwksBody(string $kid, string $publicB64url): array
    {
        return [
            'keys' => [
                ['kty' => 'OKP', 'crv' => 'Ed25519', 'kid' => $kid, 'x' => $publicB64url],
            ],
        ];
    }

    public function test_pinea_la_clave_del_servidor_junto_a_la_de_desarrollo(): void
    {
        // El servidor de licencias firma con su propio `kid`; el helper de desarrollo, con otro.
        // Ambas tienen que convivir: si sólo cabe una, activar la del servidor invalida de golpe
        // toda licencia ya emitida por el helper.
        foreach ([
            'LICENSE_KID' => 'dev-k1',
            'LICENSE_PUBLIC_KEY' => 'clave-de-desarrollo',
            'LICENSE_KID_2' => 'reportesia-f42ce27e',
            'LICENSE_PUBLIC_KEY_2' => 'clave-del-servidor',
        ] as $clave => $valor) {
            $_ENV[$clave] = $valor;
            $_SERVER[$clave] = $valor;
        }

        $config = require config_path('license.php');

        $this->assertSame([
            'dev-k1' => 'clave-de-desarrollo',
            'reportesia-f42ce27e' => 'clave-del-servidor',
        ], $config['keys']);
    }

    public function test_selecciona_la_clave_publica_por_kid(): void
    {
        $k1 = $this->licenseKeypair('k1');
        $k2 = $this->licenseKeypair('k2');

        config([
            'license.keys' => ['k1' => $k1['public'], 'k2' => $k2['public']],
            'app.url' => 'https://localhost',
        ]);

        // Token firmado con la clave del kid 'k2'.
        $token = $this->makeToken($this->defaultClaims(['kid' => 'k2']), $k2['secret'], 'k2');

        $claims = (new LicenseVerifier())->verify($token);

        $this->assertSame('k2', $claims['kid']);
    }

    public function test_kid_rotado_verifica_si_su_clave_esta_presente(): void
    {
        $viejo = $this->licenseKeypair('k1');
        $nuevo = $this->licenseKeypair('k2');

        // Durante el solapamiento ambas claves están configuradas.
        config([
            'license.keys' => ['k1' => $viejo['public'], 'k2' => $nuevo['public']],
            'app.url' => 'https://localhost',
        ]);

        $token = $this->makeToken($this->defaultClaims(['kid' => 'k1']), $viejo['secret'], 'k1');

        $this->assertSame('k1', (new LicenseVerifier())->verify($token)['kid']);
    }

    public function test_kid_sin_clave_configurada_falla(): void
    {
        $k1 = $this->licenseKeypair('k1');

        config([
            'license.keys' => ['k1' => $k1['public']],
            'app.url' => 'https://localhost',
        ]);

        $token = $this->makeToken($this->defaultClaims(['kid' => 'k2']), $k1['secret'], 'k2');

        $this->expectException(LicenseInvalidException::class);
        (new LicenseVerifier())->verify($token);
    }

    public function test_resuelve_kid_no_pineado_via_jwks_de_red(): void
    {
        $server = $this->licenseKeypair('srv-k1');
        $this->configureJwks();
        config(['license.keys' => []]);

        Http::fake([
            'licensing.test/api/v1/products/reportesia/jwks' => Http::response(
                $this->jwksBody('srv-k1', $server['public'])
            ),
        ]);

        $token = $this->makeToken($this->defaultClaims(['kid' => 'srv-k1']), $server['secret'], 'srv-k1');

        $claims = (new LicenseVerifier())->verify($token);

        $this->assertSame('srv-k1', $claims['kid']);
    }

    public function test_clave_pineada_gana_sobre_jwks_de_red_por_defecto(): void
    {
        $pinned = $this->licenseKeypair('k1');
        $network = $this->licenseKeypair('k1'); // mismo kid, otro par de claves
        $this->configureJwks();
        config(['license.keys' => ['k1' => $pinned['public']], 'license.trust_network_jwks' => false]);

        Http::fake([
            'licensing.test/api/v1/products/reportesia/jwks' => Http::response(
                $this->jwksBody('k1', $network['public'])
            ),
        ]);

        // Token firmado con la clave pineada: debe verificar.
        $token = $this->makeToken($this->defaultClaims(['kid' => 'k1']), $pinned['secret'], 'k1');
        $claims = (new LicenseVerifier())->verify($token);
        $this->assertSame('k1', $claims['kid']);

        // La resolución de un kid pineado no debe pegarle a la red.
        Http::assertNothingSent();

        // Un token firmado con la clave "de red" para el mismo kid debe rechazarse
        // porque la pineada gana sin opt-in.
        $tokenDeRed = $this->makeToken($this->defaultClaims(['kid' => 'k1']), $network['secret'], 'k1');
        $this->expectException(LicenseInvalidException::class);
        (new LicenseVerifier())->verify($tokenDeRed);
    }

    public function test_jwks_puede_sobreescribir_clave_pineada_con_opt_in(): void
    {
        $pinned = $this->licenseKeypair('k1');
        $network = $this->licenseKeypair('k1'); // mismo kid, otro par de claves
        $this->configureJwks();
        config(['license.keys' => ['k1' => $pinned['public']], 'license.trust_network_jwks' => true]);

        Http::fake([
            'licensing.test/api/v1/products/reportesia/jwks' => Http::response(
                $this->jwksBody('k1', $network['public'])
            ),
        ]);

        $tokenDeRed = $this->makeToken($this->defaultClaims(['kid' => 'k1']), $network['secret'], 'k1');

        $claims = (new LicenseVerifier())->verify($tokenDeRed);

        $this->assertSame('k1', $claims['kid']);
    }

    public function test_fallo_de_red_al_resolver_jwks_es_fail_safe(): void
    {
        $server = $this->licenseKeypair('srv-k1');
        $this->configureJwks();
        config(['license.keys' => []]);

        Http::fake(function () {
            throw new ConnectionException('No se pudo conectar al servidor de licencias.');
        });

        $token = $this->makeToken($this->defaultClaims(['kid' => 'srv-k1']), $server['secret'], 'srv-k1');

        $this->expectException(LicenseInvalidException::class);
        (new LicenseVerifier())->verify($token);
    }

    public function test_jwks_malformado_es_fail_safe(): void
    {
        $server = $this->licenseKeypair('srv-k1');
        $this->configureJwks();
        config(['license.keys' => []]);

        Http::fake([
            'licensing.test/api/v1/products/reportesia/jwks' => Http::response('no-soy-json-valido', 200),
        ]);

        $token = $this->makeToken($this->defaultClaims(['kid' => 'srv-k1']), $server['secret'], 'srv-k1');

        $this->expectException(LicenseInvalidException::class);
        (new LicenseVerifier())->verify($token);
    }

    public function test_jwks_se_cachea_y_no_se_vuelve_a_pedir_por_red(): void
    {
        $server = $this->licenseKeypair('srv-k1');
        $this->configureJwks();
        config(['license.keys' => []]);

        Http::fake([
            'licensing.test/api/v1/products/reportesia/jwks' => Http::response(
                $this->jwksBody('srv-k1', $server['public'])
            ),
        ]);

        $token = $this->makeToken($this->defaultClaims(['kid' => 'srv-k1']), $server['secret'], 'srv-k1');

        (new LicenseVerifier())->verify($token);
        (new LicenseVerifier())->verify($token);

        Http::assertSentCount(1);
    }
}
