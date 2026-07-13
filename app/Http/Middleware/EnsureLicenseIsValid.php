<?php

namespace App\Http\Middleware;

use App\Enums\LicenseStatus;
use App\Models\ActivityLog;
use App\Models\LicenseState;
use App\Services\LicenseVerifier;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureLicenseIsValid
{
    public function __construct(private readonly LicenseVerifier $verifier)
    {
    }

    /**
     * Enforcement OFFLINE por request: NO hace red ni verificación
     * criptográfica, solo lee el estado persistido y resuelve el status local.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $state = LicenseState::current();
        $status = $this->verifier->resolveStatus($state);

        $this->recordTransition($state, $status);

        return match ($status) {
            LicenseStatus::Valid => $next($request),
            LicenseStatus::Grace => $this->passWithBanner($request, $next, $state),
            default => $this->block($request), // Unlicensed | Blocked
        };
    }

    /**
     * Deja pasar pero comparte la fecha límite de gracia para el banner global.
     */
    private function passWithBanner(Request $request, Closure $next, LicenseState $state): Response
    {
        View::share(
            'licenseGraceUntil',
            $state->valid_until->copy()->addDays((int) config('license.grace_days', 14))
        );

        return $next($request);
    }

    /**
     * Bloqueo consciente del rol: el admin va a la pantalla de activación para
     * re-activar; el no-admin va a una pantalla read-only de "contactá a tu
     * administrador" (evita el 403 de la pantalla admin-only).
     */
    private function block(Request $request): Response
    {
        $user = $request->user();

        if ($user && ! $user->isAdmin()) {
            return redirect()->route('license.blocked.show');
        }

        return redirect()->route('license.activation.show');
    }

    /**
     * Audita la transición hacia gracia o bloqueo UNA sola vez (cuando cambia),
     * no en cada request. Persiste el nuevo estado de forma silenciosa para no
     * duplicar el registro vía LogsActivity.
     */
    private function recordTransition(?LicenseState $state, LicenseStatus $status): void
    {
        if ($state === null || $state->enforcement_status === $status) {
            return;
        }

        if (in_array($status, [LicenseStatus::Grace, LicenseStatus::Blocked], true)) {
            ActivityLog::create([
                'log_name' => 'LicenseState',
                'description' => "Licencia: transición a {$status->value}",
                'subject_type' => LicenseState::class,
                'subject_id' => $state->id,
                'causer_type' => Auth::check() ? Auth::user()::class : null,
                'causer_id' => Auth::id(),
                'properties' => [
                    'from' => $state->enforcement_status?->value,
                    'to' => $status->value,
                ],
                'event' => 'license_transition',
            ]);
        }

        $state->forceFill(['enforcement_status' => $status])->saveQuietly();
    }
}
