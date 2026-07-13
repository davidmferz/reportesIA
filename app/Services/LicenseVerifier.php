<?php

namespace App\Services;

use App\Enums\LicenseStatus;
use App\Exceptions\LicenseInvalidException;
use App\Models\LicenseState;
use App\Models\User;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class LicenseVerifier
{
    /**
     * Claims que la spec exige en todo token de licencia.
     *
     * @var list<string>
     */
    private const REQUIRED_CLAIMS = [
        'client_id', 'domain', 'valid_from', 'valid_until',
        'max_users', 'issued_at', 'schema_version', 'kid',
    ];

    /**
     * Verifica un JWS de licencia OFFLINE: firma EdDSA (Ed25519), algoritmo
     * forzado (rechaza alg:none y confusión de algoritmo), clave pública
     * seleccionada por `kid`, claims requeridos y binding de dominio.
     * La verificación criptográfica se delega a firebase/php-jwt (NO se maneja
     * la firma a mano). Devuelve los claims validados.
     *
     * @return array<string, mixed>
     *
     * @throws LicenseInvalidException
     */
    public function verify(string $token): array
    {
        $kid = $this->extractKid($token);
        $key = $kid !== null ? $this->resolveKey($kid) : null;

        if ($key === null) {
            throw new LicenseInvalidException(
                "No se pudo resolver una clave pública de licencia para el kid '{$kid}' (config/license.php ni JWKS)."
            );
        }

        try {
            // JWT::decode fuerza el algoritmo de la Key (EdDSA) y valida la
            // firma. alg:none y la confusión de algoritmo se rechazan acá
            // adentro. La clave ya fue seleccionada por `kid` en resolveKey().
            $decoded = JWT::decode($token, [$kid => $key]);
        } catch (Throwable $e) {
            throw new LicenseInvalidException('Token de licencia inválido: ' . $e->getMessage(), previous: $e);
        }

        $claims = (array) $decoded;

        $this->assertRequiredClaims($claims);
        $this->assertDomainMatches($claims);

        return $claims;
    }

    /**
     * Verifica un token y, si es válido, lo persiste como estado de licencia
     * vigente (modelo de una sola fila) auditando el alta. Si es inválido,
     * lanza y NO toca la persistencia.
     *
     * @throws LicenseInvalidException
     */
    public function activate(string $token, User $by): LicenseState
    {
        $claims = $this->verify($token);

        return DB::transaction(function () use ($token, $claims, $by): LicenseState {
            // Una sola fila: la nueva licencia reemplaza a la anterior.
            LicenseState::query()->delete();

            return LicenseState::create([
                'raw_token' => $token,
                'payload' => $claims,
                'valid_from' => $this->toCarbon($claims['valid_from']),
                'valid_until' => $this->toCarbon($claims['valid_until']),
                'max_users' => (int) $claims['max_users'],
                'last_check_at' => Carbon::now(),
                'last_check_result' => 'active',
                'last_seen_at' => Carbon::now(),
                'activated_at' => Carbon::now(),
                'activated_by' => $by->id,
            ]);
        });
    }

    /**
     * Resuelve el estado de enforcement de una licencia de forma OFFLINE,
     * solo con la fila persistida, la config local y el reloj. NO hace red ni
     * verificación criptográfica.
     */
    public function resolveStatus(?LicenseState $state): LicenseStatus
    {
        if ($state === null) {
            return LicenseStatus::Unlicensed;
        }

        if ($state->last_check_result === 'revoked') {
            return LicenseStatus::Blocked;
        }

        $now = Carbon::now();

        // Rollback de reloj: si el sistema está antes de la última validación
        // exitosa, no se puede confiar en el tiempo local → bloquear y forzar
        // revalidación online (license:check) en vez de comparar fechas locales.
        if ($state->last_seen_at !== null && $now->lt($state->last_seen_at)) {
            return LicenseStatus::Blocked;
        }

        if ($now->lte($state->valid_until)) {
            return LicenseStatus::Valid;
        }

        $graceUntil = $state->valid_until->copy()->addDays((int) config('license.grace_days', 14));

        if ($now->lte($graceUntil)) {
            return LicenseStatus::Grace;
        }

        return LicenseStatus::Blocked;
    }

    /**
     * Construye el keyset de verificación indexado por `kid` desde la config
     * local. Cada clave queda ligada al algoritmo EdDSA.
     *
     * @return array<string, Key>
     */
    private function keySet(): array
    {
        $keys = [];

        foreach ((array) config('license.keys', []) as $kid => $public) {
            if (is_string($public) && $public !== '') {
                $keys[(string) $kid] = new Key($public, 'EdDSA');
            }
        }

        return $keys;
    }

    /**
     * Extrae el `kid` del header de un JWS SIN verificar la firma todavía.
     * Cualquier problema de forma (segmentos, base64, JSON) se resuelve como
     * "no hay kid" en vez de tirar, para que el llamador falle limpio con
     * LicenseInvalidException.
     */
    private function extractKid(string $token): ?string
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        try {
            $header = json_decode(JWT::urlsafeB64Decode($parts[0]), true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        return is_array($header) && isset($header['kid']) && is_string($header['kid'])
            ? $header['kid']
            : null;
    }

    /**
     * Resuelve la clave pública de verificación para un `kid`: las claves
     * pineadas en config/license.php SIEMPRE ganan salvo opt-in explícito
     * (license.trust_network_jwks=true), que permite que la JWKS de red
     * pise una clave pineada para ese mismo kid. Si el kid no está pineado,
     * se intenta resolver contra la JWKS cacheada/de red sin necesidad de
     * opt-in (así rota el server sin redeploy del cliente).
     */
    private function resolveKey(string $kid): ?Key
    {
        $pinned = $this->keySet();
        $trustNetwork = (bool) config('license.trust_network_jwks', false);

        if (isset($pinned[$kid]) && ! $trustNetwork) {
            return $pinned[$kid];
        }

        $network = $this->jwksKeySet();

        return $network[$kid] ?? $pinned[$kid] ?? null;
    }

    /**
     * Keyset de verificación resuelto desde la JWKS del servidor (cacheada).
     * Fail-safe: cualquier error de red, HTTP o de forma del JSON devuelve
     * un keyset vacío en vez de propagar la excepción.
     *
     * @return array<string, Key>
     */
    private function jwksKeySet(): array
    {
        $jwks = $this->fetchJwksCached();

        if ($jwks === null) {
            return [];
        }

        try {
            return JWK::parseKeySet($jwks, 'EdDSA');
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchJwksCached(): ?array
    {
        $url = $this->jwksUrl();

        if ($url === null) {
            return null;
        }

        $cacheKey = 'license:jwks:' . md5($url);

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $fetched = $this->fetchJwks($url);

        if ($fetched !== null) {
            $ttlHours = (int) config('license.jwks_cache_hours', 24);
            Cache::put($cacheKey, $fetched, Carbon::now()->addHours($ttlHours));
        }

        return $fetched;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchJwks(string $url): ?array
    {
        try {
            $response = Http::timeout(10)->connectTimeout(5)->get($url);
        } catch (Throwable) {
            // Servidor caído / timeout / DNS: no es un error fatal, sólo
            // significa que este kid no se puede resolver por red ahora.
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $json = $response->json();

        return is_array($json) && isset($json['keys']) && is_array($json['keys']) ? $json : null;
    }

    /**
     * URL de la JWKS: explícita (license.jwks_url) o derivada de
     * {server_url}/api/v1/products/{product_id}/jwks.
     */
    private function jwksUrl(): ?string
    {
        $explicit = config('license.jwks_url');
        if (is_string($explicit) && $explicit !== '') {
            return $explicit;
        }

        $serverUrl = config('license.server_url');
        $productId = config('license.product_id');

        if (is_string($serverUrl) && $serverUrl !== '' && is_string($productId) && $productId !== '') {
            return rtrim($serverUrl, '/') . '/api/v1/products/' . $productId . '/jwks';
        }

        return null;
    }

    /**
     * @param array<string, mixed> $claims
     *
     * @throws LicenseInvalidException
     */
    private function assertRequiredClaims(array $claims): void
    {
        foreach (self::REQUIRED_CLAIMS as $claim) {
            if (! array_key_exists($claim, $claims) || $claims[$claim] === null || $claims[$claim] === '') {
                throw new LicenseInvalidException("Falta el claim requerido en la licencia: {$claim}.");
            }
        }
    }

    /**
     * @param array<string, mixed> $claims
     *
     * @throws LicenseInvalidException
     */
    private function assertDomainMatches(array $claims): void
    {
        $expected = $this->expectedDomain();

        if ($expected !== null && $claims['domain'] !== $expected) {
            throw new LicenseInvalidException(
                "El dominio de la licencia ({$claims['domain']}) no coincide con el host de la aplicación ({$expected})."
            );
        }
    }

    private function expectedDomain(): ?string
    {
        $url = (string) config('app.url');

        return parse_url($url, PHP_URL_HOST) ?: ($url !== '' ? $url : null);
    }

    private function toCarbon(mixed $value): Carbon
    {
        return is_numeric($value)
            ? Carbon::createFromTimestamp((int) $value)
            : Carbon::parse((string) $value);
    }
}
