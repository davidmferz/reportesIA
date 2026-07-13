<?php

namespace App\Console\Commands;

use App\Exceptions\LicenseInvalidException;
use App\Models\LicenseState;
use App\Services\LicenseVerifier;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Throwable;

class LicenseCheckCommand extends Command
{
    protected $signature = 'license:check';

    protected $description = 'Phone-home diario contra el licensing-server: renueva, confirma o revoca la licencia local.';

    public function handle(LicenseVerifier $verifier): int
    {
        $state = LicenseState::current();

        if ($state === null) {
            $this->warn('No hay ninguna licencia activada todavía; se omite el phone-home.');
            return self::SUCCESS;
        }

        $serverUrl = config('license.server_url');
        $clientId = config('license.client_id');

        if (! is_string($serverUrl) || $serverUrl === '' || ! is_string($clientId) || $clientId === '') {
            $this->warn('license.server_url / license.client_id sin configurar; se omite el phone-home.');
            return self::SUCCESS;
        }

        $url = rtrim($serverUrl, '/') . "/api/v1/licenses/{$clientId}/validate";

        try {
            // Offline-first: el servidor NO es punto único de falla. Si no
            // responde, el estado local queda intacto y se sigue operando
            // con el último token válido hasta valid_until (+ gracia).
            $response = Http::timeout(10)->connectTimeout(5)->retry(1, 500)->post($url, [
                'domain' => $this->currentDomain(),
                'schema_version' => $state->payload['schema_version'] ?? 1,
                'current_token' => $state->raw_token,
            ]);
        } catch (Throwable $e) {
            $this->warn('No se pudo contactar al servidor de licencias: ' . $e->getMessage());
            return self::SUCCESS;
        }

        if (! $response->successful()) {
            $this->warn("El servidor de licencias respondió con error ({$response->status()}); se mantiene el estado local.");
            return self::SUCCESS;
        }

        $body = $response->json();
        $status = is_array($body) ? ($body['status'] ?? null) : null;

        return match ($status) {
            'revoked' => $this->handleRevoked($state),
            'active' => $this->handleActive($state, $verifier, is_array($body) ? ($body['token'] ?? null) : null),
            default => $this->handleUnexpected($status),
        };
    }

    private function handleRevoked(LicenseState $state): int
    {
        $state->forceFill([
            'last_check_at' => Carbon::now(),
            'last_check_result' => 'revoked',
        ])->save(); // save() (no saveQuietly): la revocación tiene que auditarse via LogsActivity.

        $this->error('Licencia revocada por el servidor.');

        return self::SUCCESS;
    }

    private function handleActive(LicenseState $state, LicenseVerifier $verifier, mixed $newToken): int
    {
        if (! is_string($newToken) || $newToken === '') {
            // El servidor confirma vigencia sin emitir un token nuevo.
            $state->forceFill([
                'last_check_at' => Carbon::now(),
                'last_check_result' => 'active',
                'last_seen_at' => Carbon::now(),
            ])->save();

            $this->info('Licencia confirmada por el servidor.');

            return self::SUCCESS;
        }

        try {
            // El token renovado se re-verifica localmente (firma, kid,
            // dominio, claims) antes de reemplazar el estado persistido:
            // nunca se confía ciegamente en lo que devuelve la red.
            $claims = $verifier->verify($newToken);
        } catch (LicenseInvalidException $e) {
            $this->error('El servidor devolvió un token que no pasa la verificación local: ' . $e->getMessage());

            return self::FAILURE;
        }

        $state->forceFill([
            'raw_token' => $newToken,
            'payload' => $claims,
            'valid_from' => $this->toCarbon($claims['valid_from']),
            'valid_until' => $this->toCarbon($claims['valid_until']),
            'max_users' => (int) $claims['max_users'],
            'last_check_at' => Carbon::now(),
            'last_check_result' => 'active',
            'last_seen_at' => Carbon::now(),
        ])->save();

        $this->info('Licencia renovada.');

        return self::SUCCESS;
    }

    private function handleUnexpected(mixed $status): int
    {
        $this->warn('Respuesta inesperada del servidor de licencias (status=' . var_export($status, true) . '); se mantiene el estado local.');

        return self::SUCCESS;
    }

    private function currentDomain(): ?string
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
