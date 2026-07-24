<x-layouts.crm>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center space-x-2 text-sm text-hando-gray-500 dark:text-hando-gray-400 mb-1">
                    <a href="{{ route('admin.report-types.index') }}" class="hover:text-hando-primary">Tipos de Reportes</a>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span>{{ $reportType->nombre }}</span>
                </div>
                <h1 class="text-2xl font-bold text-hando-text-light dark:text-hando-text-dark">Capítulos</h1>
                <p class="mt-1 text-sm text-hando-black-500 dark:text-hando-black-400">Gestiona los capítulos del tipo de reporte: <strong>{{ $reportType->nombre }}</strong></p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.report-types.index') }}">
                    <x-hando-button variant="secondary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Volver
                    </x-hando-button>
                </a>
                @if(Auth::user()->is_admin)
                <a href="{{ route('admin.chapters.create', $reportType) }}">
                    <x-hando-button variant="primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Agregar Capítulo
                    </x-hando-button>
                </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="max-w-full mx-auto">
        @if(session('success'))
        <div class="mb-6 relative overflow-hidden rounded-hando bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 shadow-hando">
            <div class="flex items-center p-4">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-green-900 dark:text-green-100">
                        {{ session('success') }}
                    </p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="flex-shrink-0 ml-4 text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-6 rounded-hando bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 shadow-hando">
            <div class="flex items-center p-4">
                <svg class="h-5 w-5 flex-shrink-0 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <p class="ml-3 text-sm font-medium text-red-900 dark:text-red-100">{{ session('error') }}</p>
            </div>
        </div>
        @endif

        {{-- Estructura sugerida del catálogo. Solo crea capítulos: no toca el prompt ni el entrenamiento. --}}
        @if($estructuraSugerida->isNotEmpty())
        <div x-data="{ abierto: false }" class="mb-6 rounded-hando border border-hando-border-light dark:border-hando-border-dark bg-white dark:bg-hando-card-dark p-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-hando-text-light dark:text-hando-text-dark">
                        Estructura sugerida del catálogo
                    </p>
                    <p class="mt-1 text-xs text-hando-gray-500 dark:text-hando-gray-400">
                        {{ $reportType->catalogServicePath() }} — {{ $estructuraSugerida->count() }} apartados
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" x-on:click="abierto = !abierto"
                            class="text-xs text-hando-gray-500 dark:text-hando-gray-400 underline hover:no-underline">
                        <span x-text="abierto ? 'Ocultar' : 'Ver apartados'">Ver apartados</span>
                    </button>
                    <form action="{{ route('admin.chapters.apply-structure', $reportType) }}" method="POST"
                          @if($chapters->isNotEmpty())
                          onsubmit="return confirm('Esto reemplazará los {{ $chapters->count() }} capítulos actuales por los {{ $estructuraSugerida->count() }} del catálogo. Los actuales quedan recuperables. ¿Continuar?');"
                          @endif>
                        @csrf
                        @if($chapters->isNotEmpty())
                            <input type="hidden" name="replace" value="1">
                        @endif
                        <x-hando-button variant="primary" type="submit">
                            {{ $chapters->isNotEmpty() ? 'Reemplazar por la estructura sugerida' : 'Cargar estructura sugerida' }}
                        </x-hando-button>
                    </form>
                </div>
            </div>
            <ol x-show="abierto" x-cloak class="mt-4 space-y-1 border-t border-hando-border-light dark:border-hando-border-dark pt-4">
                @foreach($estructuraSugerida as $section)
                    <li class="text-xs text-hando-gray-500 dark:text-hando-gray-400">
                        <span class="font-medium text-hando-text-light dark:text-hando-text-dark">{{ $section->orden }}. {{ $section->apartado }}</span>
                        — {{ $section->contenido }}
                    </li>
                @endforeach
            </ol>
        </div>
        @endif

        <!-- Banner informativo del tipo de reporte -->
        <div class="mb-6 bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl shadow-2xl border-4 border-blue-400 p-5">
            <div class="flex items-center space-x-4">
                <div class="h-12 w-12 bg-white/20 rounded-lg flex items-center justify-center ring-4 ring-white/20">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-xs font-semibold text-blue-100 uppercase tracking-widest">Tipo de Reporte</p>
                    <p class="text-2xl font-black text-black">{{ $reportType->nombre }}</p>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="px-3 py-1.5 bg-white/90 text-blue-800 text-sm font-bold rounded-lg flex items-center">
                        <span class="text-blue-500 mr-1">#</span> ID: {{ $reportType->id }}
                    </span>
                    <span class="px-3 py-1.5 {{ $chapters->count() > 0 ? 'bg-green-500' : 'bg-orange-500' }} text-black text-sm font-bold rounded-lg">
                        {{ $chapters->count() }} capítulo(s)
                    </span>
                </div>
            </div>
        </div>

        <x-crm.card :padding="false">
            <div class="overflow-x-auto">
                <table class="w-full table-fixed divide-y divide-hando-border-light dark:divide-hando-border-dark">
                    <thead class="bg-hando-gray-50 dark:bg-hando-gray-800">
                        <tr>
                            <th scope="col" class="w-[8%] px-6 py-3 text-left text-xs font-medium text-hando-gray-500 dark:text-hando-gray-400 uppercase tracking-wider">
                                Orden
                            </th>
                            <th scope="col" class="w-[25%] px-6 py-3 text-left text-xs font-medium text-hando-gray-500 dark:text-hando-gray-400 uppercase tracking-wider">
                                Nombre
                            </th>
                            <th scope="col" class="w-[32%] px-6 py-3 text-left text-xs font-medium text-hando-gray-500 dark:text-hando-gray-400 uppercase tracking-wider">
                                Descripción
                            </th>
                            <th scope="col" class="w-[15%] px-6 py-3 text-left text-xs font-medium text-hando-gray-500 dark:text-hando-gray-400 uppercase tracking-wider">
                                Creado por
                            </th>
                            <th scope="col" class="w-[20%] px-6 py-3 text-left text-xs font-medium text-hando-gray-500 dark:text-hando-gray-400 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-hando-card-dark divide-y divide-hando-border-light dark:divide-hando-border-dark">
                        @forelse($chapters as $chapter)
                        <tr class="hover:bg-hando-gray-50 dark:hover:bg-hando-gray-700 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-hando-text-light dark:text-hando-text-dark">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 font-bold">
                                    {{ $chapter->orden }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-hando-text-light dark:text-hando-text-dark">
                                {{ $chapter->nombre }}
                            </td>
                            <td class="px-6 py-4 text-sm text-hando-gray-500 dark:text-hando-gray-400">
                                {{ $chapter->descripcion ? Str::limit($chapter->descripcion, 60) : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-hando-gray-500 dark:text-hando-gray-400">
                                {{ $chapter->creator ? $chapter->creator->name : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                @if(Auth::user()->is_admin)
                                <a href="{{ route('admin.chapters.edit', [$reportType, $chapter]) }}" class="text-green-600 hover:text-green-700 transition-colors">
                                    Editar
                                </a>
                                <form action="{{ route('admin.chapters.destroy', [$reportType, $chapter]) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de eliminar este capítulo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-hando-danger hover:text-red-700 transition-colors">
                                        Eliminar
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-hando-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                    <p class="text-hando-gray-500 dark:text-hando-gray-400 text-sm">No hay capítulos registrados para este tipo de reporte</p>
                                    @if(Auth::user()->is_admin)
                                    <a href="{{ route('admin.chapters.create', $reportType) }}" class="mt-4">
                                        <x-hando-button variant="primary" size="sm">
                                            Crear el primer capítulo
                                        </x-hando-button>
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-crm.card>
    </div>
</x-layouts.crm>
