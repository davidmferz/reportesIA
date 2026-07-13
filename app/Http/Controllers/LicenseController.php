<?php

namespace App\Http\Controllers;

use App\Exceptions\LicenseInvalidException;
use App\Models\LicenseState;
use App\Services\LicenseVerifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class LicenseController extends Controller
{
    /**
     * Pantalla de activación (solo admin). Muestra el estado vigente si existe.
     */
    public function show(): View
    {
        return view('license.activation', [
            'state' => LicenseState::current(),
        ]);
    }

    /**
     * Activa una licencia a partir del token pegado. Verifica antes de
     * persistir: un token inválido no cambia nada.
     */
    public function store(Request $request, LicenseVerifier $verifier): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        try {
            $verifier->activate(trim($validated['token']), $request->user());
        } catch (LicenseInvalidException $e) {
            return Redirect::route('license.activation.show')
                ->withErrors(['token' => 'La licencia no es válida: ' . $e->getMessage()]);
        }

        return Redirect::route('license.activation.show')->with('status', 'license-activated');
    }

    /**
     * Pantalla read-only para usuarios no-admin cuando la licencia está
     * bloqueada: "contactá a tu administrador". Sin requerir rol admin, para
     * evitar el 403 de la pantalla de activación.
     */
    public function blocked(): View
    {
        return view('license.blocked');
    }
}
