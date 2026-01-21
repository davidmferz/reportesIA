<x-layouts.crm>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-hando-text-light dark:text-hando-text-dark">Entrenamiento de IA</h1>
                <p class="mt-1 text-sm text-hando-gray-500 dark:text-hando-gray-400">Entrena la IA con ejemplos de entrada/salida por tipo de reporte</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-full mx-auto">
        <!-- Información sobre el módulo -->
        <div class="mb-6 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 border border-purple-200 dark:border-purple-800 rounded-hando p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-semibold text-purple-900 dark:text-purple-100">¿Cómo funciona el entrenamiento?</h3>
                    <p class="mt-1 text-sm text-purple-800 dark:text-purple-200">
                        1. Sube archivos de <strong>entrada</strong> y <strong>salida</strong> en el módulo de Gestión de Archivos<br>
                        2. La IA aprende el patrón de transformación entrada → salida<br>
                        3. Luego puedes generar nuevos documentos subiendo solo archivos de entrada
                    </p>
                </div>
            </div>
        </div>

        @if(session('success'))
        <div class="mb-6 relative overflow-hidden rounded-hando bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 shadow-hando">
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

        <x-crm.card :padding="false">
            <div class="overflow-x-auto">
                <table class="w-full table-fixed divide-y divide-hando-border-light dark:divide-hando-border-dark">
                    <thead class="bg-hando-gray-50 dark:bg-hando-gray-800">
                        <tr>
                            <th scope="col" class="w-[35%] px-6 py-3 text-left text-xs font-medium text-hando-gray-500 dark:text-hando-gray-400 uppercase tracking-wider">
                                Tipo de Reporte
                            </th>
                            <th scope="col" class="w-[15%] px-6 py-3 text-center text-xs font-medium text-hando-gray-500 dark:text-hando-gray-400 uppercase tracking-wider">
                                Archivos
                            </th>
                            <th scope="col" class="w-[20%] px-6 py-3 text-center text-xs font-medium text-hando-gray-500 dark:text-hando-gray-400 uppercase tracking-wider">
                                Estado IA
                            </th>
                            <th scope="col" class="w-[30%] px-6 py-3 text-left text-xs font-medium text-hando-gray-500 dark:text-hando-gray-400 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-hando-card-dark divide-y divide-hando-border-light dark:divide-hando-border-dark">
                        @forelse($reportTypes as $reportType)
                        <tr class="hover:bg-purple-50 dark:hover:bg-purple-900/10 transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-purple-100 dark:bg-purple-900/30 rounded-hando flex items-center justify-center group-hover:bg-purple-200 dark:group-hover:bg-purple-800 transition-colors">
                                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <span class="font-semibold text-hando-text-light dark:text-hando-text-dark group-hover:text-purple-600 transition-colors">
                                            {{ $reportType->nombre }}
                                        </span>
                                        @if($reportType->training)
                                        <p class="text-xs text-hando-gray-500">
                                            {{ $reportType->training->examples_count }} ejemplos
                                        </p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($reportType->files_count > 0)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-hando-primary">
                                        {{ $reportType->files_count }} archivos
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-hando-gray-100 dark:bg-hando-gray-700 text-hando-gray-500">
                                        Sin archivos
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($reportType->training)
                                    @php $badge = $reportType->training->status_badge; @endphp
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                        @if($badge['color'] === 'green') bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300
                                        @elseif($badge['color'] === 'yellow') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300
                                        @elseif($badge['color'] === 'red') bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300
                                        @else bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300
                                        @endif">
                                        @if($badge['color'] === 'green')
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        @endif
                                        {{ $badge['text'] }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-500">
                                        No entrenado
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <a href="{{ route('admin.ai-training.show', $reportType) }}" class="text-purple-600 hover:text-purple-700 transition-colors">
                                    Ver Detalles
                                </a>
                                @if($reportType->training && $reportType->training->status === 'ready')
                                    <a href="{{ route('admin.ai-training.generate.create', $reportType) }}" class="text-green-600 hover:text-green-700 transition-colors">
                                        Generar
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-hando-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                    </svg>
                                    <p class="text-hando-gray-500 dark:text-hando-gray-400 text-sm">No hay tipos de reportes disponibles</p>
                                    <a href="{{ route('admin.report-types.index') }}" class="mt-4">
                                        <x-hando-button variant="primary" size="sm">
                                            Crear Tipo de Reporte
                                        </x-hando-button>
                                    </a>
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
