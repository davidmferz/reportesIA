<x-layouts.crm>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-hando-text-light dark:text-hando-text-dark">Palabras Prohibidas</h1>
                <p class="mt-1 text-sm text-hando-gray-500 dark:text-hando-gray-400">
                    Lista global que se inyecta en <span class="font-semibold">todos</span> los prompts. La IA es instruida a NO usarlas, y se sanea el output a nivel código si la IA igual las usa.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-full mx-auto space-y-6">
        @if (session('success'))
            <div class="rounded-hando bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 shadow-hando">
                <div class="flex items-center p-4">
                    <svg class="h-5 w-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="ml-3 text-sm font-medium text-green-900 dark:text-green-100">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-hando bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 shadow-hando">
                <div class="p-4">
                    <p class="text-sm font-semibold text-red-900 dark:text-red-100 mb-2">Hay errores en el formulario:</p>
                    <ul class="list-disc list-inside text-sm text-red-800 dark:text-red-200 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- Forms ocultos asociados por id (HTML5 form attribute) -->
        @foreach ($words as $w)
            <form id="update-form-{{ $w->id }}" action="{{ route('admin.forbidden-words.update', $w) }}" method="POST" class="hidden">
                @csrf @method('PUT')
            </form>
            <form id="delete-form-{{ $w->id }}" action="{{ route('admin.forbidden-words.destroy', $w) }}" method="POST" class="hidden">
                @csrf @method('DELETE')
            </form>
        @endforeach

        <!-- Formulario de alta -->
        <x-crm.card>
            <h3 class="text-lg font-bold text-hando-text-light dark:text-hando-text-dark mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-hando-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Agregar palabra
            </h3>
            <form action="{{ route('admin.forbidden-words.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                @csrf
                <div class="md:col-span-4">
                    <label class="block text-xs font-semibold text-hando-gray-600 dark:text-hando-gray-400 uppercase mb-1">Palabra</label>
                    <input type="text" name="word" value="{{ old('word') }}" required maxlength="100"
                           class="w-full px-3 py-2 rounded-hando border border-hando-border-light dark:border-hando-border-dark bg-white dark:bg-hando-card-dark text-hando-text-light dark:text-hando-text-dark focus:outline-none focus:ring-2 focus:ring-hando-primary"
                           placeholder="ej: optimizar">
                </div>
                <div class="md:col-span-7">
                    <label class="block text-xs font-semibold text-hando-gray-600 dark:text-hando-gray-400 uppercase mb-1">Motivo (opcional)</label>
                    <input type="text" name="reason" value="{{ old('reason') }}" maxlength="255"
                           class="w-full px-3 py-2 rounded-hando border border-hando-border-light dark:border-hando-border-dark bg-white dark:bg-hando-card-dark text-hando-text-light dark:text-hando-text-dark focus:outline-none focus:ring-2 focus:ring-hando-primary"
                           placeholder="ej: lenguaje comercial no adecuado para reportes técnicos">
                </div>
                <div class="md:col-span-1">
                    <x-hando-button type="submit" variant="primary" class="w-full">Agregar</x-hando-button>
                </div>
            </form>
        </x-crm.card>

        <!-- Tabla de palabras -->
        <x-crm.card :padding="false">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-hando-gray-50 dark:bg-hando-gray-800 border-b border-hando-border-light dark:border-hando-border-dark">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-hando-gray-600 dark:text-hando-gray-400 uppercase tracking-wider">Palabra</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-hando-gray-600 dark:text-hando-gray-400 uppercase tracking-wider">Motivo</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-hando-gray-600 dark:text-hando-gray-400 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-hando-gray-600 dark:text-hando-gray-400 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-hando-border-light dark:divide-hando-border-dark">
                        @forelse ($words as $w)
                            <tr x-data="{ editing: false }" class="hover:bg-hando-gray-50 dark:hover:bg-hando-gray-700 transition-colors">
                                <td class="px-6 py-4 align-middle">
                                    <span x-show="!editing" class="font-mono text-sm font-semibold text-hando-text-light dark:text-hando-text-dark">{{ $w->word }}</span>
                                    <input x-show="editing" form="update-form-{{ $w->id }}" type="text" name="word" value="{{ $w->word }}" required maxlength="100"
                                           class="w-full px-2 py-1 text-sm rounded border border-hando-border-light dark:border-hando-border-dark bg-white dark:bg-hando-card-dark text-hando-text-light dark:text-hando-text-dark">
                                </td>
                                <td class="px-6 py-4 align-middle">
                                    <span x-show="!editing" class="text-sm text-hando-gray-600 dark:text-hando-gray-400">{{ $w->reason ?: '—' }}</span>
                                    <input x-show="editing" form="update-form-{{ $w->id }}" type="text" name="reason" value="{{ $w->reason }}" maxlength="255"
                                           class="w-full px-2 py-1 text-sm rounded border border-hando-border-light dark:border-hando-border-dark bg-white dark:bg-hando-card-dark text-hando-text-light dark:text-hando-text-dark">
                                </td>
                                <td class="px-6 py-4 align-middle">
                                    <span x-show="!editing" class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold {{ $w->active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-hando-gray-200 text-hando-gray-600 dark:bg-hando-gray-700 dark:text-hando-gray-400' }}">
                                        {{ $w->active ? 'Activa' : 'Inactiva' }}
                                    </span>
                                    <label x-show="editing" class="inline-flex items-center text-sm">
                                        <input form="update-form-{{ $w->id }}" type="checkbox" name="active" value="1" {{ $w->active ? 'checked' : '' }}
                                               class="rounded border-hando-border-light dark:border-hando-border-dark text-hando-primary focus:ring-hando-primary">
                                        <span class="ml-2 text-hando-text-light dark:text-hando-text-dark">Activa</span>
                                    </label>
                                </td>
                                <td class="px-6 py-4 align-middle text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <template x-if="!editing">
                                            <div class="flex items-center gap-2">
                                                <button type="button" @click="editing = true" class="text-hando-primary hover:text-hando-primary-hover text-sm font-medium">Editar</button>
                                                <button type="submit" form="delete-form-{{ $w->id }}"
                                                        onclick="return confirm('¿Eliminar definitivamente la palabra «{{ $w->word }}»?')"
                                                        class="text-red-600 hover:text-red-800 dark:text-red-400 text-sm font-medium">Eliminar</button>
                                            </div>
                                        </template>
                                        <template x-if="editing">
                                            <div class="flex items-center gap-2">
                                                <x-hando-button type="submit" form="update-form-{{ $w->id }}" variant="primary" size="sm">Guardar</x-hando-button>
                                                <button type="button" @click="editing = false" class="text-hando-gray-500 hover:text-hando-gray-700 text-sm">Cancelar</button>
                                            </div>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <p class="text-base font-semibold text-hando-gray-600 dark:text-hando-gray-400">Aún no hay palabras prohibidas</p>
                                    <p class="text-sm text-hando-gray-500 dark:text-hando-gray-500 mt-1">Agregá la primera con el formulario de arriba.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($words->hasPages())
                <div class="px-6 py-4 bg-hando-gray-50 dark:bg-hando-gray-800 border-t border-hando-border-light dark:border-hando-border-dark">
                    {{ $words->links() }}
                </div>
            @endif
        </x-crm.card>
    </div>
</x-layouts.crm>
