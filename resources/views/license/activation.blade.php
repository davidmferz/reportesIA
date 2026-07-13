<x-layouts.crm>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-hando-text-light dark:text-hando-text-dark">Activación de licencia</h1>
        <p class="mt-1 text-sm text-hando-gray-500 dark:text-hando-gray-400">
            Pegá el token de licencia emitido para esta instalación.
        </p>
    </x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        @if (session('status') === 'license-activated')
            <div class="rounded-hando bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 px-4 py-3 text-sm">
                Licencia activada correctamente.
            </div>
        @endif

        @if ($state)
            <x-crm.card title="Licencia actual">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-hando-gray-500 dark:text-hando-gray-400">Cliente</dt>
                        <dd class="font-medium text-hando-text-light dark:text-hando-text-dark">{{ $state->payload['client_id'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-hando-gray-500 dark:text-hando-gray-400">Válida hasta</dt>
                        <dd class="font-medium text-hando-text-light dark:text-hando-text-dark">{{ optional($state->valid_until)->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-hando-gray-500 dark:text-hando-gray-400">Usuarios máximos</dt>
                        <dd class="font-medium text-hando-text-light dark:text-hando-text-dark">{{ $state->max_users }}</dd>
                    </div>
                    <div>
                        <dt class="text-hando-gray-500 dark:text-hando-gray-400">Última verificación</dt>
                        <dd class="font-medium text-hando-text-light dark:text-hando-text-dark">{{ optional($state->last_check_at)?->diffForHumans() ?? '—' }}</dd>
                    </div>
                </dl>
            </x-crm.card>
        @endif

        <x-crm.card title="Ingresar token">
            <form method="POST" action="{{ route('license.activation.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="token" class="block text-sm font-medium text-hando-text-light dark:text-hando-text-dark mb-1">
                        Token de licencia (JWS / EdDSA)
                    </label>
                    <textarea
                        id="token"
                        name="token"
                        rows="6"
                        class="w-full rounded-hando border border-hando-border-light dark:border-hando-border-dark bg-white dark:bg-hando-gray-800 text-hando-text-light dark:text-hando-text-dark font-mono text-xs px-3 py-2 focus:ring-hando-primary focus:border-hando-primary"
                        placeholder="eyJhbGciOiJFZERTQSIsImtpZCI6Li4ufQ...">{{ old('token') }}</textarea>
                    @error('token')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-hando bg-hando-primary hover:bg-hando-primary-hover text-white text-sm font-medium transition-colors">
                    Activar licencia
                </button>
            </form>
        </x-crm.card>
    </div>
</x-layouts.crm>
