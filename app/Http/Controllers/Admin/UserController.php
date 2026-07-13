<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\LicenseState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {
        $users = User::latest()->paginate(10);
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'is_admin' => ['boolean'],
        ]);

        $this->assertUserCapacityAvailable();

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_admin'] = $request->has('is_admin');

        User::create($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario creado exitosamente.');
    }

    public function show(User $user)
    {
        $activities = ActivityLog::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->orWhere(function ($query) use ($user) {
                $query->where('causer_type', User::class)
                      ->where('causer_id', $user->id);
            })
            ->latest()
            ->paginate(10);

        return view('admin.users.show', compact('user', 'activities'));
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'is_admin' => ['boolean'],
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['is_admin'] = $request->has('is_admin');

        $user->update($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario eliminado exitosamente.');
    }

    /**
     * Activa o desactiva un usuario. Desactivar SIEMPRE está permitido (solo
     * libera cupo). Reactivar respeta el mismo tope de usuarios activos que
     * la licencia impone en `store()`.
     */
    public function toggleActive(User $user)
    {
        if ($user->is_active) {
            $user->update(['is_active' => false]);

            return redirect()->route('admin.users.index')
                ->with('success', 'Usuario desactivado exitosamente.');
        }

        $this->assertUserCapacityAvailable();

        $user->update(['is_active' => true]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario reactivado exitosamente.');
    }

    /**
     * Rechaza la operación si activar un usuario (crear o reactivar) superaría
     * el tope de la licencia. Defensa en profundidad: el middleware `license`
     * ya bloquea el grupo admin cuando no hay licencia, pero el controller no
     * confía en eso y vuelve a decidir.
     */
    private function assertUserCapacityAvailable(): void
    {
        $maxUsers = LicenseState::current()?->max_users;

        // Sin licencia no hay cupo que otorgar: se rechaza.
        $hasCapacity = $maxUsers !== null && User::active()->count() < $maxUsers;

        if (! $hasCapacity) {
            throw ValidationException::withMessages([
                'max_users' => 'Se alcanzó el límite de usuarios activos permitido por la licencia.',
            ]);
        }
    }
}
