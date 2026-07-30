<x-layouts.crm>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.ai-training.index') }}" class="text-hando-gray-500 hover:text-hando-gray-700 dark:text-hando-gray-400 dark:hover:text-hando-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-hando-text-light dark:text-hando-text-dark">Entrenamiento: {{ $reportType->nombre }}</h1>
                    <p class="mt-1 text-sm text-hando-gray-500 dark:text-hando-gray-400">Gestiona el entrenamiento de IA para este tipo de reporte</p>
                </div>
            </div>
            <div class="flex space-x-3">
                @if($training && $training->status === 'ready')
                    <a href="{{ route('admin.ai-training.generate.create', $reportType) }}">
                        <x-hando-button variant="primary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            Generar Documento
                        </x-hando-button>
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="max-w-full mx-auto space-y-6">
        @if(session('success'))
        <div class="relative overflow-hidden rounded-hando bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 shadow-hando">
            <div class="flex items-center p-4">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-green-900 dark:text-green-100">{{ session('success') }}</p>
                </div>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="relative overflow-hidden rounded-hando bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 shadow-hando">
            <div class="flex items-center p-4">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-red-900 dark:text-red-100">{{ session('error') }}</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Estado del Entrenamiento -->
        <x-crm.card>
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-bold text-hando-text-light dark:text-hando-text-dark">Estado del Entrenamiento</h2>
                @if($training)
                    @php $badge = $training->status_badge; @endphp
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold
                        @if($badge['color'] === 'green') bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300
                        @elseif($badge['color'] === 'yellow') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300
                        @elseif($badge['color'] === 'red') bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300
                        @else bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300
                        @endif">
                        @if($badge['color'] === 'green')
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        @elseif($badge['color'] === 'yellow')
                            <svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        @endif
                        {{ $badge['text'] }}
                    </span>
                @else
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        No entrenado
                    </span>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <!-- Archivos de Entrenamiento -->
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-hando p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10 bg-blue-200 dark:bg-blue-800 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-blue-900 dark:text-blue-100">Archivos</p>
                            <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $reportType->files->count() }}</p>
                        </div>
                    </div>
                </div>

                <!-- Grupos/Capítulos -->
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-hando p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10 bg-purple-200 dark:bg-purple-800 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-purple-900 dark:text-purple-100">Capítulos</p>
                            <p class="text-2xl font-bold text-purple-700 dark:text-purple-300">{{ $fileGroups->count() }}</p>
                        </div>
                    </div>
                </div>

                <!-- Ejemplos Procesados -->
                <div class="bg-green-50 dark:bg-green-900/20 rounded-hando p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10 bg-green-200 dark:bg-green-800 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-green-900 dark:text-green-100">Ejemplos IA</p>
                            <p class="text-2xl font-bold text-green-700 dark:text-green-300">{{ $training ? $training->examples_count : 0 }}</p>
                        </div>
                    </div>
                </div>

                <!-- Último Entrenamiento -->
                <div class="bg-orange-50 dark:bg-orange-900/20 rounded-hando p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10 bg-orange-200 dark:bg-orange-800 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-orange-900 dark:text-orange-100">Último Entreno</p>
                            <p class="text-sm font-bold text-orange-700 dark:text-orange-300">
                                {{ $training && $training->last_trained_at ? $training->last_trained_at->format('d/m/Y H:i') : 'Nunca' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botón de Entrenar -->
            <div class="flex items-center justify-between pt-4 border-t border-hando-border-light dark:border-hando-border-dark">
                <p class="text-sm text-hando-gray-500 dark:text-hando-gray-400">
                    @if($fileGroups->count() > 0)
                        Hay {{ $fileGroups->count() }} capítulos disponibles para entrenar.
                    @else
                        No hay capítulos disponibles. Primero sube archivos en <a href="{{ route('admin.report-files.show', $reportType) }}" class="text-hando-primary hover:underline">Gestión de Archivos</a>.
                    @endif
                </p>
                <div class="flex space-x-3">
                    @if($training && $training->status === 'ready')
                        <a href="{{ route('admin.ai-training.generations', $reportType) }}">
                            <x-hando-button variant="secondary">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                Ver Generaciones
                            </x-hando-button>
                        </a>
                    @endif
                    @if($fileGroups->count() > 0)
                        <form action="{{ route('admin.ai-training.train', $reportType) }}" method="POST" class="inline-block">
                            @csrf
                            <x-hando-button variant="primary" type="submit">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                                {{ $training ? 'Re-entrenar IA' : 'Entrenar IA' }}
                            </x-hando-button>
                        </form>
                    @endif
                </div>
            </div>
        </x-crm.card>

        <!-- Lista de Capítulos -->
        @if($fileGroups->count() > 0)
        <x-crm.card>
            <h2 class="text-lg font-bold text-hando-text-light dark:text-hando-text-dark mb-4">Capítulos de Entrenamiento</h2>
            <p class="text-sm text-hando-gray-500 dark:text-hando-gray-400 mb-6">
                Cada capítulo contiene archivos de entrada y su correspondiente archivo de salida esperado.
            </p>

            <div class="space-y-4">
                @foreach($fileGroups as $grupoId => $files)
                    @php
                        $firstFile = $files->first();
                        $archivosEntrada = $files->where('tipo_archivo', 'entrada');
                        $archivoSalida = $files->where('tipo_archivo', 'salida')->first();
                    @endphp

                    <div class="border border-hando-border-light dark:border-hando-border-dark rounded-hando p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h3 class="font-semibold text-hando-text-light dark:text-hando-text-dark">
                                    {{ $firstFile->chapter?->nombre ?? $firstFile->capitulo ?? 'Sin nombre' }}
                                </h3>
                                <p class="text-xs text-hando-gray-500">
                                    {{ $archivosEntrada->count() }} archivos de entrada | {{ $archivoSalida ? '1 archivo de salida' : 'Sin salida' }}
                                </p>
                            </div>
                            @if($archivosEntrada->count() > 0 && $archivoSalida)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Completo
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 dark:bg-orange-900/30 text-orange-700">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    Incompleto
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Archivos de Entrada -->
                            <div class="bg-blue-50 dark:bg-blue-900/10 rounded p-3">
                                <p class="text-xs font-semibold text-blue-700 dark:text-blue-300 mb-2">Archivos de Entrada:</p>
                                <ul class="space-y-1">
                                    @foreach($archivosEntrada as $archivo)
                                        <li class="text-sm text-hando-gray-700 dark:text-hando-gray-300 flex items-center">
                                            <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            {{ $archivo->nombre_original }}
                                            <span class="text-xs text-hando-gray-400 ml-2">({{ $archivo->tamano_formateado }})</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <!-- Archivo de Salida -->
                            <div class="bg-green-50 dark:bg-green-900/10 rounded p-3">
                                <p class="text-xs font-semibold text-green-700 dark:text-green-300 mb-2">Archivo de Salida:</p>
                                @if($archivoSalida)
                                    <div class="text-sm text-hando-gray-700 dark:text-hando-gray-300 flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        {{ $archivoSalida->nombre_original }}
                                        <span class="text-xs text-hando-gray-400 ml-2">({{ $archivoSalida->tamano_formateado }})</span>
                                    </div>
                                @else
                                    <p class="text-sm text-orange-600 dark:text-orange-400">No hay archivo de salida</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-crm.card>
        @endif

        <!-- Enlace a Gestión de Archivos -->
        <div class="flex justify-center">
            <a href="{{ route('admin.report-files.show', $reportType) }}" class="text-sm text-hando-primary hover:text-hando-primary-hover transition-colors flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                Ir a Gestión de Archivos para agregar más ejemplos
            </a>
        </div>
    </div>
</x-layouts.crm>
