<x-layouts.crm>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center space-x-2 text-sm text-hando-gray-500 dark:text-hando-gray-400 mb-1">
                    <a href="{{ route('admin.report-types.index') }}" class="hover:text-hando-primary">Tipos de Reportes</a>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <a href="{{ route('admin.chapters.index', $reportType) }}" class="hover:text-hando-primary">{{ $reportType->nombre }}</a>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span>Nuevo Capítulo</span>
                </div>
                <h1 class="text-2xl font-bold text-hando-text-light dark:text-hando-text-dark">Agregar Capítulo</h1>
                <p class="mt-1 text-sm text-hando-gray-500 dark:text-hando-gray-400">Agrega un nuevo capítulo al tipo de reporte: <strong>{{ $reportType->nombre }}</strong></p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <!-- Banner informativo del tipo de reporte -->
        <div class="mb-6 bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl shadow-2xl border-4 border-blue-400 p-6 hover:scale-[1.01] transition-transform">
            <div class="flex items-center space-x-4">
                <div class="h-10 w-10 bg-white/20 rounded-lg flex items-center justify-center ring-4 ring-white/20">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-xs font-semibold text-blue-100 uppercase tracking-widest">Agregarás capítulo a:</p>
                    <p class="text-xl font-black text-white">{{ $reportType->nombre }}</p>
                </div>
                <span class="px-3 py-1.5 bg-white/90 text-blue-800 text-sm font-bold rounded-lg flex items-center">
                    <span class="text-blue-500 mr-1">#</span> ID: {{ $reportType->id }}
                </span>
            </div>
        </div>

        <x-crm.card title="Información del Capítulo">
            <form action="{{ route('admin.chapters.store', $reportType) }}" method="POST">
                @csrf

                <div class="space-y-6">
                    <div>
                        <x-hando-label for="nombre" value="Nombre del Capítulo" :required="true" />
                        <x-hando-input
                            id="nombre"
                            name="nombre"
                            type="text"
                            class="mt-1 block w-full"
                            :value="old('nombre')"
                            placeholder="Ej: Capítulo 1 - Introducción"
                            required
                        />
                        @error('nombre')
                            <p class="mt-1 text-sm text-hando-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <x-hando-label for="descripcion" value="Descripción" />
                        <textarea
                            id="descripcion"
                            name="descripcion"
                            rows="3"
                            class="mt-1 block w-full px-4 py-2 border border-hando-border-light dark:border-hando-border-dark rounded-hando bg-white dark:bg-hando-card-dark text-hando-text-light dark:text-hando-text-dark focus:ring-2 focus:ring-hando-primary focus:border-transparent transition-colors"
                            placeholder="Descripción opcional del capítulo..."
                        >{{ old('descripcion') }}</textarea>
                        @error('descripcion')
                            <p class="mt-1 text-sm text-hando-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <x-hando-label for="orden" value="Orden" />
                        <x-hando-input
                            id="orden"
                            name="orden"
                            type="number"
                            class="mt-1 block w-full"
                            :value="old('orden')"
                            placeholder="Se asignará automáticamente si se deja vacío"
                            min="0"
                        />
                        <p class="mt-1 text-xs text-hando-gray-500 dark:text-hando-gray-400">Define el orden en que aparecerá este capítulo. Si lo dejas vacío, se asignará al final.</p>
                        @error('orden')
                            <p class="mt-1 text-sm text-hando-danger">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-8 flex justify-end space-x-3">
                    <a href="{{ route('admin.chapters.index', $reportType) }}">
                        <x-hando-button type="button" variant="secondary">
                            Cancelar
                        </x-hando-button>
                    </a>
                    <x-hando-button type="submit" variant="primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Guardar Capítulo
                    </x-hando-button>
                </div>
            </form>
        </x-crm.card>
    </div>
</x-layouts.crm>
