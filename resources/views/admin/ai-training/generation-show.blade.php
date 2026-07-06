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

        @php
            $promptMessages = $generation->prompt_messages ?? [];

            $systemMessages = [];
            $exampleMessages = [];
            $finalUserMessage = null;

            if (!empty($promptMessages)) {
                $lastUserIndex = null;
                foreach ($promptMessages as $idx => $msg) {
                    if (($msg['role'] ?? null) === 'user') {
                        $lastUserIndex = $idx;
                    }
                }

                foreach ($promptMessages as $idx => $msg) {
                    $role = $msg['role'] ?? 'unknown';
                    if ($role === 'system') {
                        $systemMessages[] = $msg;
                    } elseif ($idx === $lastUserIndex) {
                        $finalUserMessage = $msg;
                    } else {
                        $exampleMessages[] = $msg;
                    }
                }
            }

            $messageCount = count($promptMessages);
        @endphp

        <!-- Prompt enviado a la IA (colapsable) -->
        <x-crm.card>
            <details class="group">
                <summary class="flex items-center justify-between cursor-pointer list-none select-none">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                        <h3 class="text-lg font-bold text-hando-text-light dark:text-hando-text-dark">
                            Prompt enviado a la IA
                        </h3>
                        @if($messageCount > 0)
                            <span class="ml-3 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300">
                                {{ $messageCount }} {{ $messageCount === 1 ? 'mensaje' : 'mensajes' }}
                            </span>
                        @endif
                    </div>
                    <span class="flex items-center text-sm text-hando-gray-500 dark:text-hando-gray-400 group-hover:text-hando-primary">
                        <span class="group-open:hidden">Mostrar</span>
                        <span class="hidden group-open:inline">Ocultar</span>
                        <svg class="w-4 h-4 ml-1 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </span>
                </summary>

                <div class="mt-5 space-y-5">
                    @if(empty($promptMessages))
                        <div class="bg-hando-gray-50 dark:bg-hando-gray-800 rounded-hando p-6 text-center border border-dashed border-hando-gray-300 dark:border-hando-gray-700">
                            <svg class="w-10 h-10 text-hando-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm font-medium text-hando-gray-600 dark:text-hando-gray-400">Prompt no disponible</p>
                            <p class="text-xs text-hando-gray-500 mt-1">Esta generación es anterior a la captura de prompts. Las generaciones nuevas guardan automáticamente lo enviado a la IA.</p>
                        </div>
                    @else
                        <p class="text-xs text-hando-gray-500 dark:text-hando-gray-400 italic">
                            Snapshot del primer envío a OpenAI. Los reintentos por validación quedan registrados en el reporte de validación.
                        </p>

                        @foreach($systemMessages as $sysIdx => $sysMsg)
                            <div class="rounded-hando border border-purple-200 dark:border-purple-800 bg-purple-50 dark:bg-purple-900/10 overflow-hidden">
                                <div class="flex items-center justify-between px-4 py-2 bg-purple-100 dark:bg-purple-900/30 border-b border-purple-200 dark:border-purple-800">
                                    <div class="flex items-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase bg-purple-200 text-purple-800 dark:bg-purple-800 dark:text-purple-100">System</span>
                                        <span class="ml-2 text-sm font-semibold text-purple-900 dark:text-purple-200">
                                            @if($sysIdx === 0)
                                                Instrucciones del entrenamiento
                                            @else
                                                Palabras prohibidas globales
                                            @endif
                                        </span>
                                    </div>
                                    <span class="text-[10px] text-purple-600 dark:text-purple-400">
                                        {{ number_format(strlen($sysMsg['content'] ?? '')) }} chars
                                    </span>
                                </div>
                                <div class="p-4 max-h-72 overflow-y-auto">
                                    <pre class="text-xs text-hando-gray-700 dark:text-hando-gray-300 whitespace-pre-wrap font-mono leading-relaxed">{{ $sysMsg['content'] ?? '' }}</pre>
                                </div>
                            </div>
                        @endforeach

                        @if(!empty($exampleMessages))
                            @php $exampleCount = (int) floor(count($exampleMessages) / 2); @endphp
                            <details class="group/ex rounded-hando border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/10">
                                <summary class="flex items-center justify-between px-4 py-2 bg-amber-100 dark:bg-amber-900/30 border-b border-amber-200 dark:border-amber-800 cursor-pointer list-none">
                                    <div class="flex items-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase bg-amber-200 text-amber-800 dark:bg-amber-800 dark:text-amber-100">Few-shot</span>
                                        <span class="ml-2 text-sm font-semibold text-amber-900 dark:text-amber-200">
                                            Ejemplos de referencia
                                        </span>
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-amber-200/60 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200">
                                            {{ $exampleCount }} {{ $exampleCount === 1 ? 'ejemplo' : 'ejemplos' }}
                                        </span>
                                    </div>
                                    <svg class="w-4 h-4 text-amber-700 dark:text-amber-400 transition-transform group-open/ex:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </summary>
                                <div class="p-4 space-y-3">
                                    @foreach($exampleMessages as $exMsg)
                                        @php $isUser = ($exMsg['role'] ?? '') === 'user'; @endphp
                                        <div class="rounded border {{ $isUser ? 'border-blue-200 dark:border-blue-800 bg-blue-50/50 dark:bg-blue-900/10' : 'border-green-200 dark:border-green-800 bg-green-50/50 dark:bg-green-900/10' }}">
                                            <div class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider {{ $isUser ? 'text-blue-700 dark:text-blue-300' : 'text-green-700 dark:text-green-300' }}">
                                                {{ $isUser ? 'Entrada de ejemplo' : 'Salida de ejemplo' }}
                                            </div>
                                            <div class="px-3 pb-2 max-h-48 overflow-y-auto">
                                                <pre class="text-[11px] text-hando-gray-700 dark:text-hando-gray-300 whitespace-pre-wrap font-mono leading-relaxed">{{ $exMsg['content'] ?? '' }}</pre>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @endif

                        @if($finalUserMessage)
                            <div class="rounded-hando border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/10 overflow-hidden">
                                <div class="flex items-center justify-between px-4 py-2 bg-blue-100 dark:bg-blue-900/30 border-b border-blue-200 dark:border-blue-800">
                                    <div class="flex items-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase bg-blue-200 text-blue-800 dark:bg-blue-800 dark:text-blue-100">User</span>
                                        <span class="ml-2 text-sm font-semibold text-blue-900 dark:text-blue-200">
                                            Entrada a procesar
                                        </span>
                                    </div>
                                    <span class="text-[10px] text-blue-600 dark:text-blue-400">
                                        {{ number_format(strlen($finalUserMessage['content'] ?? '')) }} chars
                                    </span>
                                </div>
                                <div class="p-4 max-h-72 overflow-y-auto">
                                    <pre class="text-xs text-hando-gray-700 dark:text-hando-gray-300 whitespace-pre-wrap font-mono leading-relaxed">{{ $finalUserMessage['content'] ?? '' }}</pre>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </details>
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
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-hando-text-light dark:text-hando-text-dark flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Contenido Generado
                    </h3>
                    @if(isset($generation->validation_passed))
                        @if($generation->validation_passed)
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                ✓ Validación OK
                                @if(($generation->validation_attempts ?? 1) > 1)
                                    <span class="ml-1 text-[10px] opacity-75">({{ $generation->validation_attempts }} intentos)</span>
                                @endif
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                ⚠ Validación parcial
                            </span>
                        @endif
                        @if($generation->sanitized_post_hoc)
                            <span class="ml-2 inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300" title="Se removieron palabras prohibidas a nivel código">
                                🛡 Saneado
                            </span>
                        @endif
                        @if($generation->truncated_post_hoc ?? false)
                            <span class="ml-2 inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300" title="Se cortó al límite de palabras a nivel código tras los reintentos">
                                ✂ Truncado
                            </span>
                        @endif
                    @endif
                </div>

                @php
                    $validationViolations = $generation->validation_result['violations'] ?? [];
                    $similarityMetric = $generation->validation_result['metrics']['training_output_similarity'] ?? null;
                @endphp
                @if(!empty($similarityMetric) && isset($similarityMetric['best_score']))
                    @php
                        $similarityPercent = (int) round(($similarityMetric['best_score'] ?? 0) * 100);
                        $similarityThreshold = (int) round(($similarityMetric['threshold'] ?? 0) * 100);
                        $bestReference = $similarityMetric['best_reference'] ?? [];
                    @endphp
                    <div class="mb-4 p-3 rounded-hando bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800">
                        <details>
                            <summary class="text-sm font-semibold text-blue-800 dark:text-blue-300 cursor-pointer">
                                Juez de similitud contra salidas de entrenamiento:
                                {{ $similarityPercent }}%
                                <span class="font-normal">(mínimo {{ $similarityThreshold }}%)</span>
                            </summary>
                            <div class="mt-2 text-xs text-hando-gray-700 dark:text-hando-gray-300 space-y-1">
                                <p>
                                    Mejor referencia:
                                    <span class="font-semibold">
                                        {{ $bestReference['capitulo'] ?? 'Sin capítulo' }}
                                    </span>
                                    @if(isset($bestReference['grupo_id']))
                                        · Grupo {{ $bestReference['grupo_id'] }}
                                    @endif
                                </p>
                                <p>Evalúa vocabulario técnico, encabezados, estructura, longitud relativa y frases compartidas.</p>
                            </div>
                        </details>
                    </div>
                @endif
                @if(!empty($validationViolations))
                    <div class="mb-4 p-3 rounded-hando bg-yellow-50 dark:bg-yellow-900/10 border border-yellow-200 dark:border-yellow-800">
                        <details>
                            <summary class="text-sm font-semibold text-yellow-800 dark:text-yellow-300 cursor-pointer">
                                Reporte de validación ({{ count($validationViolations) }} hallazgo{{ count($validationViolations) === 1 ? '' : 's' }})
                            </summary>
                            <ul class="mt-2 space-y-1.5 text-xs">
                                @foreach($validationViolations as $v)
                                    <li class="flex items-start">
                                        <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold mr-2 mt-0.5
                                            {{ $v['severity'] === 'critical' ? 'bg-red-200 text-red-800' : 'bg-yellow-200 text-yellow-800' }}">
                                            {{ strtoupper($v['severity']) }}
                                        </span>
                                        <span class="text-hando-gray-700 dark:text-hando-gray-300">{{ $v['detail'] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    </div>
                @endif

                @if($generation->output_content)
                    <div class="bg-green-50 dark:bg-green-900/10 rounded-hando p-4 max-h-96 overflow-y-auto border border-green-200 dark:border-green-800 prose prose-sm dark:prose-invert max-w-none">
                        {!! \Illuminate\Support\Str::markdown($generation->display_output) !!}
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
