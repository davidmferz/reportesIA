<x-layouts.crm>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-hando-text-light dark:text-hando-text-dark">Registro de Actividad</h1>
                <p class="mt-1 text-sm text-hando-gray-500 dark:text-hando-gray-400">Quién hizo qué y cuándo: tipos de reporte, prompts, toggles, palabras prohibidas, entrenamientos y generaciones.</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-full mx-auto">
        {{-- Filtros --}}
        <x-crm.card class="mb-6">
            <form method="GET" action="{{ route('admin.activity-logs.index') }}"
                  class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
                <div class="lg:col-span-2">
                    <label class="block text-xs font-semibold text-hando-gray-500 dark:text-hando-gray-400 mb-1">Usuario</label>
                    <select name="causer_id" class="w-full px-3 py-2 text-sm border border-hando-border-light dark:border-hando-border-dark rounded-hando bg-white dark:bg-hando-gray-800">
                        <option value="">Todos</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" @selected(request('causer_id') == $u->id)>{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-hando-gray-500 dark:text-hando-gray-400 mb-1">Objeto</label>
                    <select name="log_name" class="w-full px-3 py-2 text-sm border border-hando-border-light dark:border-hando-border-dark rounded-hando bg-white dark:bg-hando-gray-800">
                        <option value="">Todos</option>
                        @foreach($logNames as $name)
                            <option value="{{ $name }}" @selected(request('log_name') === $name)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-hando-gray-500 dark:text-hando-gray-400 mb-1">Acción</label>
                    <select name="event" class="w-full px-3 py-2 text-sm border border-hando-border-light dark:border-hando-border-dark rounded-hando bg-white dark:bg-hando-gray-800">
                        <option value="">Todas</option>
                        @foreach(['created' => 'Creado', 'updated' => 'Modificado', 'deleted' => 'Eliminado'] as $val => $label)
                            <option value="{{ $val }}" @selected(request('event') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-hando-gray-500 dark:text-hando-gray-400 mb-1">Desde</label>
                    <input type="date" name="desde" value="{{ request('desde') }}"
                           class="w-full px-3 py-2 text-sm border border-hando-border-light dark:border-hando-border-dark rounded-hando bg-white dark:bg-hando-gray-800">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-hando-gray-500 dark:text-hando-gray-400 mb-1">Hasta</label>
                    <input type="date" name="hasta" value="{{ request('hasta') }}"
                           class="w-full px-3 py-2 text-sm border border-hando-border-light dark:border-hando-border-dark rounded-hando bg-white dark:bg-hando-gray-800">
                </div>
                <div class="sm:col-span-2 lg:col-span-6 flex gap-2">
                    <x-hando-button variant="primary" size="sm" type="submit">Filtrar</x-hando-button>
                    <a href="{{ route('admin.activity-logs.index') }}">
                        <x-hando-button variant="secondary" size="sm" type="button">Limpiar</x-hando-button>
                    </a>
                </div>
            </form>
        </x-crm.card>

        {{-- Tabla --}}
        <x-crm.card :padding="false">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-hando-border-light dark:divide-hando-border-dark text-sm">
                    <thead class="bg-hando-gray-50 dark:bg-hando-gray-800/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-hando-gray-500 dark:text-hando-gray-400">Fecha y hora</th>
                            <th class="px-4 py-3 text-left font-semibold text-hando-gray-500 dark:text-hando-gray-400">Usuario</th>
                            <th class="px-4 py-3 text-left font-semibold text-hando-gray-500 dark:text-hando-gray-400">Acción</th>
                            <th class="px-4 py-3 text-left font-semibold text-hando-gray-500 dark:text-hando-gray-400">Objeto</th>
                            <th class="px-4 py-3 text-left font-semibold text-hando-gray-500 dark:text-hando-gray-400">Cambios</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-hando-border-light dark:divide-hando-border-dark">
                        @forelse($logs as $log)
                            @php
                                $attrs = data_get($log->properties, 'attributes', []);
                                $old = data_get($log->properties, 'old', []);
                                $ignore = ['updated_at', 'created_at', 'updated_by', 'created_by', 'deleted_by'];
                                $eventColors = [
                                    'created' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                                    'updated' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                                    'deleted' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                                ];
                                $eventLabels = ['created' => 'Creado', 'updated' => 'Modificado', 'deleted' => 'Eliminado'];
                            @endphp
                            <tr class="hover:bg-hando-gray-50 dark:hover:bg-hando-gray-800/30 align-top">
                                <td class="px-4 py-3 whitespace-nowrap text-hando-text-light dark:text-hando-text-dark">
                                    {{ $log->created_at?->format('d/m/Y H:i:s') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ $log->causer?->name ?? 'Sistema' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $eventColors[$log->event] ?? 'bg-hando-gray-100 text-hando-gray-600' }}">
                                        {{ $eventLabels[$log->event] ?? $log->event }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-hando-text-light dark:text-hando-text-dark">
                                    {{ $log->log_name }} <span class="text-hando-gray-400">#{{ $log->subject_id }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if($log->event === 'updated')
                                        @php
                                            $changes = [];
                                            foreach ($attrs as $k => $v) {
                                                if (in_array($k, $ignore, true)) continue;
                                                $before = $old[$k] ?? null;
                                                if ($before != $v) {
                                                    $changes[$k] = [$before, $v];
                                                }
                                            }
                                        @endphp
                                        @if(count($changes))
                                            <details>
                                                <summary class="cursor-pointer text-purple-600 hover:text-purple-700">{{ count($changes) }} campo(s) modificado(s)</summary>
                                                <div class="mt-2 space-y-1">
                                                    @foreach($changes as $campo => $par)
                                                        <div class="text-xs">
                                                            <span class="font-semibold">{{ $campo }}:</span>
                                                            <span class="text-red-500 line-through">{{ Str::limit((string) $par[0], 120) ?: '—' }}</span>
                                                            <span class="text-hando-gray-400">→</span>
                                                            <span class="text-green-600">{{ Str::limit((string) $par[1], 120) ?: '—' }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </details>
                                        @else
                                            <span class="text-hando-gray-400 text-xs">sin cambios visibles</span>
                                        @endif
                                    @else
                                        <details>
                                            <summary class="cursor-pointer text-purple-600 hover:text-purple-700">ver datos</summary>
                                            <div class="mt-2 space-y-1">
                                                @foreach($attrs as $k => $v)
                                                    @continue(in_array($k, $ignore, true))
                                                    <div class="text-xs"><span class="font-semibold">{{ $k }}:</span> {{ Str::limit((string) $v, 120) }}</div>
                                                @endforeach
                                            </div>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-hando-gray-400">No hay actividad registrada con esos filtros.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-crm.card>

        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    </div>
</x-layouts.crm>
