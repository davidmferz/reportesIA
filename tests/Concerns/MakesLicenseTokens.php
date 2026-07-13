<?php

namespace Tests\Concerns;

use Firebase\JWT\JWT;

/**
 * Helpers para firmar JWS de licencia en tests, espejando lo que emite el
 * licensing-server (Change A): JWS compacto, EdDSA/Ed25519, `kid` en el header.
 * Permite testear el verificador del cliente SIN el servidor corriendo.
 */
trait MakesLicenseTokens
{
    /**
     * Genera un par de claves Ed25519 y devuelve kid + secret/public en base64url.
     *
     * @return array{kid:string, public:string, secret:string}
     */
    protected function licenseKeypair(string $kid = 'k1'): array
    {
        $pair = sodium_crypto_sign_keypair();

        return [
            'kid' => $kid,
            'public' => $this->b64url(sodium_crypto_sign_publickey($pair)),
            'secret' => $this->b64url(sodium_crypto_sign_secretkey($pair)),
        ];
    }

    /**
     * Configura el keyset local y el dominio de la app para los tests.
     */
    protected function configureLicenseKey(string $kid, string $publicB64, string $domain = 'localhost'): void
    {
        config([
            'license.keys' => [$kid => $publicB64],
            'app.url' => "https://{$domain}",
        ]);
    }

    /**
     * Los 8 claims requeridos por la spec, con valores por defecto sensatos.
     */
    protected function defaultClaims(array $overrides = []): array
    {
        return array_merge([
            'client_id' => 'reportesia',
            'domain' => 'localhost',
            'valid_from' => '2026-01-01T00:00:00Z',
            'valid_until' => '2027-01-01T00:00:00Z',
            'max_users' => 5,
            'issued_at' => '2026-01-01T00:00:00Z',
            'schema_version' => 1,
            'kid' => 'k1',
        ], $overrides);
    }

    /**
     * Firma un JWS compacto con el algoritmo dado (EdDSA por defecto).
     */
    protected function makeToken(array $claims, string $keyB64, string $kid, string $alg = 'EdDSA'): string
    {
        return JWT::encode($claims, $keyB64, $alg, $kid);
    }

    /**
     * Arma un token SIN firma con `alg: none` (ataque). Sig vacía.
     */
    protected function makeUnsignedToken(array $claims, string $kid = 'k1'): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'none', 'kid' => $kid];

        return $this->b64url(json_encode($header))
            . '.' . $this->b64url(json_encode($claims)) . '.';
    }

    private function b64url(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }
}
