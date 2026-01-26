<x-layouts.crm>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.ai-training.generations', $reportType) }}" class="text-hando-gray-500 hover:text-hando-gray-700 dark:text-hando-gray-400 dark:hover:text-hando-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-hando-text-light dark:text-hando-text-dark">{{ $generation->titulo ?? 'Documento Generado' }}</h1>
                    <p class="mt-1 text-sm text-hando-gray-500 dark:text-hando-gray-400">{{ $reportType->nombre }}</p>
                </div>
            </div>
            <div class="flex space-x-3">
                @if($generation->isCompleted())
                    <a href="{{ route('admin.ai-training.generation.download', [$reportType, $generation]) }}">
                        <x-hando-button variant="primary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Descargar MD
                        </x-hando-button>
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto space-y-6">
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

        <!-- Información de la generación -->
        <x-crm.card>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-hando-text-light dark:text-hando-text-dark">Información de la Generación</h2>
                @php $badge = $generation->status_badge; @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                    @if($badge['color'] === 'green') bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300
                    @elseif($badge['color'] === 'yellow') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300
                    @elseif($badge['color'] === 'red') bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300
                    @else bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300
                    @endif">
                    @if($badge['color'] === 'green')
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    @endif
                    {{ $badge['text'] }}
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                @if($generation->chapter)
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-hando p-4 border border-purple-200 dark:border-purple-800">
                    <p class="text-xs font-medium text-purple-600 dark:text-purple-400 uppercase">Capítulo</p>
                    <p class="text-sm font-semibold text-purple-900 dark:text-purple-100 mt-1">
                        {{ $generation->chapter->orden }}. {{ $generation->chapter->nombre }}
                    </p>
                </div>
                @endif
                <div class="bg-hando-gray-50 dark:bg-hando-gray-800 rounded-hando p-4">
                    <p class="text-xs font-medium text-hando-gray-500 uppercase">Generado el</p>
                    <p class="text-sm font-semibold text-hando-text-light dark:text-hando-text-dark mt-1">
                        {{ $generation->generated_at ? $generation->generated_at->format('d/m/Y H:i') : 'En proceso' }}
                    </p>
                </div>
                <div class="bg-hando-gray-50 dark:bg-hando-gray-800 rounded-hando p-4">
                    <p class="text-xs font-medium text-hando-gray-500 uppercase">Usuario</p>
                    <p class="text-sm font-semibold text-hando-text-light dark:text-hando-text-dark mt-1">
                        {{ $generation->user->name ?? 'N/A' }}
                    </p>
                </div>
                <div class="bg-hando-gray-50 dark:bg-hando-gray-800 rounded-hando p-4">
                    <p class="text-xs font-medium text-hando-gray-500 uppercase">Tokens Usados</p>
                    <p class="text-sm font-semibold text-hando-text-light dark:text-hando-text-dark mt-1">
                        {{ $generation->total_tokens ? number_format($generation->total_tokens) : 'N/A' }}
                    </p>
                </div>
                <div class="bg-hando-gray-50 dark:bg-hando-gray-800 rounded-hando p-4">
                    <p class="text-xs font-medium text-hando-gray-500 uppercase">Costo Estimado</p>
                    <p class="text-sm font-semibold text-hando-text-light dark:text-hando-text-dark mt-1">
                        ${{ number_format($generation->estimated_cost, 4) }} USD
                    </p>
                </div>
            </div>

            @if($generation->hasError())
                <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-hando">
                    <p class="text-sm font-medium text-red-700 dark:text-red-300">Error:</p>
                    <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $generation->error_message }}</p>
                </div>
            @endif
        </x-crm.card>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Entrada -->
            <x-crm.card>
                <h3 class="text-lg font-bold text-hando-text-light dark:text-hando-text-dark mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    Contenido de Entrada
                </h3>
                <div class="bg-blue-50 dark:bg-blue-900/10 rounded-hando p-4 max-h-96 overflow-y-auto border border-blue-200 dark:border-blue-800">
                    <pre class="text-sm text-hando-gray-700 dark:text-hando-gray-300 whitespace-pre-wrap font-mono">{{ $generation->input_content }}</pre>
                </div>
            </x-crm.card>

            <!-- Salida -->
            <x-crm.card>
                <h3 class="text-lg font-bold text-hando-text-light dark:text-hando-text-dark mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Contenido Generado
                </h3>
                @if($generation->output_content)
                    <div class="bg-green-50 dark:bg-green-900/10 rounded-hando p-4 max-h-96 overflow-y-auto border border-green-200 dark:border-green-800 prose prose-sm dark:prose-invert max-w-none">
                        {!! \Illuminate\Support\Str::markdown($generation->output_content) !!}
                    </div>
                @else
                    <div class="bg-hando-gray-50 dark:bg-hando-gray-800 rounded-hando p-8 text-center">
                        <svg class="w-12 h-12 text-hando-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-hando-gray-500">No hay contenido generado</p>
                    </div>
                @endif
            </x-crm.card>
        </div>

        <!-- Acciones -->
        <div class="flex items-center justify-between pt-4">
            <a href="{{ route('admin.ai-training.generations', $reportType) }}" class="text-hando-primary hover:text-hando-primary-hover flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Ver todas las generaciones
            </a>
            <a href="{{ route('admin.ai-training.generate.create', $reportType) }}">
                <x-hando-button variant="secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Nueva Generación
                </x-hando-button>
            </a>
        </div>
    </div>
</x-layouts.crm>
