<x-layouts.crm>
    <x-slot name="header">
        <div class="flex items-center">
            <a href="{{ route('admin.report-files.index') }}" class="mr-4 p-2 rounded-hando text-hando-gray-600 dark:text-hando-gray-400 hover:bg-hando-gray-100 dark:hover:bg-hando-gray-700 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-hando-text-light dark:text-hando-text-dark">Configurar Prompt de IA</h1>
                <p class="mt-1 text-sm text-hando-gray-500 dark:text-hando-gray-400">
                    Tipo de reporte: <span class="font-semibold text-hando-primary">{{ $reportType->nombre }}</span>
                </p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto" x-data="promptEditor({
        initialPrompt: @js(old('prompt', $reportType->prompt) ?? ''),
        analyzeUrl: @js(route('admin.report-files.analyze-prompt')),
        previewUrl: @js(route('admin.report-files.preview-validation', $reportType)),
        csrf: @js(csrf_token()),
        initialAnalysis: @js($promptAnalysis),
    })">
        <!-- Información sobre el prompt -->
        <div class="mb-6 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 border border-purple-200 dark:border-purple-800 rounded-hando p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-semibold text-purple-900 dark:text-purple-100">Editor con análisis en vivo</h3>
                    <p class="mt-1 text-sm text-purple-800 dark:text-purple-200">
                        Mientras escribís el prompt, el sistema detecta automáticamente <strong>palabras prohibidas</strong>,
                        <strong>límites de longitud</strong> y advierte sobre instrucciones débiles. Las palabras prohibidas se aplican
                        en el validador determinístico — la IA NO puede usarlas en el output final.
                    </p>
                </div>
            </div>
        </div>

        @if(session('success'))
        <div class="mb-6 relative overflow-hidden rounded-hando bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 shadow-hando">
            <div class="flex items-center p-4">
                <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="ml-3 text-sm font-medium text-green-900 dark:text-green-100">{{ session('success') }}</p>
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Editor (2 columnas) -->
            <div class="lg:col-span-2">
                <x-crm.card>
                    <form method="POST" action="{{ route('admin.report-files.update-prompt', $reportType) }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        <!-- Configuración de modelo y modo estricto -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-hando-label for="model" value="Modelo de IA" />
                                <p class="text-xs text-hando-gray-500 dark:text-hando-gray-400 mb-2">
                                    El modelo que usará para este reporte. <code>gpt-5-mini</code> ofrece la mejor relación precio/calidad.
                                </p>
                                <select id="model" name="model"
                                    class="w-full px-3 py-2 text-sm border border-hando-border-light dark:border-hando-border-dark rounded-hando bg-white dark:bg-hando-gray-800 text-hando-text-light dark:text-hando-text-dark focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                                    <option value="">Default del sistema (gpt-5-mini)</option>
                                    @foreach($availableModels as $value => $label)
                                        <option value="{{ $value }}" @selected($reportType->model === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex flex-col justify-end">
                                <label class="inline-flex items-start cursor-pointer p-3 rounded-hando border border-hando-border-light dark:border-hando-border-dark hover:border-purple-400 transition-colors">
                                    <input type="hidden" name="modo_estricto" value="0">
                                    <input type="checkbox" id="modo_estricto" name="modo_estricto" value="1"
                                        @checked($reportType->modo_estricto)
                                        class="mt-1 h-4 w-4 text-purple-600 focus:ring-purple-500 border-hando-border-light rounded">
                                    <span class="ml-3">
                                        <span class="block text-sm font-semibold text-hando-text-light dark:text-hando-text-dark">Modo Estricto</span>
                                        <span class="block text-xs text-hando-gray-500 dark:text-hando-gray-400 mt-0.5">
                                            Desactiva ejemplos previos y patrones detectados. Solo el prompt manda.
                                            Recomendado para reportes con restricciones fuertes.
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- Datos de internet -->
                        <div class="mb-6">
                            <label class="inline-flex items-start cursor-pointer p-3 rounded-hando border border-hando-border-light dark:border-hando-border-dark hover:border-purple-400 transition-colors w-full">
                                <input type="hidden" name="usa_internet" value="0">
                                <input type="checkbox" id="usa_internet" name="usa_internet" value="1"
                                    @checked($reportType->usa_internet)
                                    class="mt-1 h-4 w-4 text-purple-600 focus:ring-purple-500 border-hando-border-light rounded">
                                <span class="ml-3">
                                    <span class="block text-sm font-semibold text-hando-text-light dark:text-hando-text-dark">Enriquecer con datos de internet</span>
                                    <span class="block text-xs text-hando-gray-500 dark:text-hando-gray-400 mt-0.5">
                                        Antes de generar, busca en internet (web_search de OpenAI) datos actuales
                                        y verificables sobre el tema y los incorpora al documento, citando fuentes.
                                        Los datos del cliente siguen mandando; internet solo complementa.
                                        Dejalo APAGADO si el reporte debe basarse exclusivamente en el documento cargado.
                                    </span>
                                </span>
                            </label>
                        </div>

                        <!-- Conocimiento del modelo -->
                        <div class="mb-6">
                            <label class="inline-flex items-start cursor-pointer p-3 rounded-hando border border-hando-border-light dark:border-hando-border-dark hover:border-purple-400 transition-colors w-full">
                                <input type="hidden" name="usa_conocimiento_modelo" value="0">
                                <input type="checkbox" id="usa_conocimiento_modelo" name="usa_conocimiento_modelo" value="1"
                                    @checked($reportType->usa_conocimiento_modelo)
                                    class="mt-1 h-4 w-4 text-purple-600 focus:ring-purple-500 border-hando-border-light rounded">
                                <span class="ml-3">
                                    <span class="block text-sm font-semibold text-hando-text-light dark:text-hando-text-dark">Aportar conocimiento del modelo</span>
                                    <span class="block text-xs text-hando-gray-500 dark:text-hando-gray-400 mt-0.5">
                                        Permite que la IA enriquezca el documento con su conocimiento experto del dominio
                                        (definiciones, marco técnico, buenas prácticas), además de los datos del cliente.
                                        Nunca inventa datos específicos del cliente (cifras, nombres, fechas): esos salen solo del documento.
                                        Dejalo APAGADO para fidelidad estricta al documento cargado.
                                    </span>
                                </span>
                            </label>
                        </div>

                        <!-- Prompt principal -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <x-hando-label for="prompt" value="Prompt para la IA" />
                                <span class="text-xs text-hando-gray-500" x-text="`${analysis.char_count} caracteres`"></span>
                            </div>
                            <p class="text-xs text-hando-gray-500 dark:text-hando-gray-400 mb-2">
                                Usá secciones tipo <code>PALABRAS PROHIBIDAS:</code>, <code>ESTRUCTURA:</code>, <code>RESTRICCIONES:</code>
                                para que el sistema las detecte automáticamente.
                            </p>
                            <textarea
                                id="prompt"
                                name="prompt"
                                rows="20"
                                x-model="prompt"
                                @input.debounce.500ms="analyzeLive()"
                                placeholder="Ejemplo de prompt restrictivo:

Genera una introducción técnica a partir del objetivo proporcionado.

REGLAS GENERALES:
- No copiar ni parafrasear el objetivo
- No agregar, suponer ni inferir información externa
- Usar lenguaje neutral, directo y técnico
- Evitar texto de relleno

PALABRAS PROHIBIDAS:
asegurar, garantizar, optimizar, implementar, ejecutar, maximizar

ESTRUCTURA OBLIGATORIA:
1. Contexto directo
2. Relación operativa
3. Necesidad de análisis
4. Enfoque
5. Resultado esperado

LÍMITE: máximo 280 palabras"
                                class="w-full px-4 py-3 text-sm font-mono border border-hando-border-light dark:border-hando-border-dark rounded-hando bg-white dark:bg-hando-gray-800 text-hando-text-light dark:text-hando-text-dark placeholder-hando-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 resize-y transition-all"
                            ></textarea>
                            @error('prompt')
                                <p class="mt-2 text-sm text-hando-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Información de Auditoría -->
                        <div class="pt-4 border-t border-hando-border-light dark:border-hando-border-dark">
                            <p class="text-xs font-semibold text-hando-gray-500 dark:text-hando-gray-400 uppercase tracking-wider mb-3">
                                Información de Auditoría
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-hando-gray-500 dark:text-hando-gray-400">Creado por:</span>
                                    <span class="ml-2 text-hando-text-light dark:text-hando-text-dark font-medium">
                                        {{ $reportType->creator ? $reportType->creator->name : 'N/A' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-hando-gray-500 dark:text-hando-gray-400">Última actualización:</span>
                                    <span class="ml-2 text-hando-text-light dark:text-hando-text-dark font-medium">
                                        {{ $reportType->updated_at?->format('d/m/Y H:i') ?? '—' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="flex items-center justify-end gap-3 pt-6 border-t border-hando-border-light dark:border-hando-border-dark">
                            <a href="{{ route('admin.report-files.index') }}">
                                <x-hando-button variant="secondary" type="button">Cancelar</x-hando-button>
                            </a>
                            <x-hando-button variant="primary" type="submit">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Guardar Prompt
                            </x-hando-button>
                        </div>
                    </form>
                </x-crm.card>
            </div>

            <!-- Panel de análisis en vivo (1 columna) -->
            <div class="lg:col-span-1 space-y-4">
                <!-- Análisis del Prompt -->
                <x-crm.card>
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-hando-text-light dark:text-hando-text-dark uppercase tracking-wider">
                            Análisis del Prompt
                        </h3>
                        <span x-show="analyzing" class="text-xs text-hando-gray-500">analizando…</span>
                    </div>

                    <!-- Palabras prohibidas detectadas -->
                    <div class="mb-4">
                        <p class="text-xs font-medium text-hando-gray-500 dark:text-hando-gray-400 mb-2">
                            Palabras prohibidas detectadas
                        </p>
                        <div class="flex flex-wrap gap-1.5" x-show="analysis.forbidden_terms.length > 0">
                            <template x-for="term in analysis.forbidden_terms">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300" x-text="term"></span>
                            </template>
                        </div>
                        <p x-show="analysis.forbidden_terms.length === 0" class="text-xs text-hando-gray-400 italic">
                            Ninguna detectada
                        </p>
                    </div>

                    <!-- Límite de palabras -->
                    <div class="mb-4 pt-3 border-t border-hando-border-light dark:border-hando-border-dark">
                        <p class="text-xs font-medium text-hando-gray-500 dark:text-hando-gray-400 mb-2">
                            Límite de palabras
                        </p>
                        <div class="flex items-center gap-3 text-sm">
                            <span x-show="analysis.word_limits.min !== null">
                                <span class="text-hando-gray-500">Mín:</span>
                                <span class="font-semibold text-hando-text-light dark:text-hando-text-dark" x-text="analysis.word_limits.min"></span>
                            </span>
                            <span x-show="analysis.word_limits.max !== null">
                                <span class="text-hando-gray-500">Máx:</span>
                                <span class="font-semibold text-hando-text-light dark:text-hando-text-dark" x-text="analysis.word_limits.max"></span>
                            </span>
                            <span x-show="analysis.word_limits.min === null && analysis.word_limits.max === null" class="text-xs text-hando-gray-400 italic">
                                Sin límite definido
                            </span>
                        </div>
                    </div>

                    <!-- Advertencias -->
                    <div class="pt-3 border-t border-hando-border-light dark:border-hando-border-dark" x-show="analysis.warnings.length > 0">
                        <p class="text-xs font-medium text-hando-gray-500 dark:text-hando-gray-400 mb-2">
                            Advertencias
                        </p>
                        <ul class="space-y-1.5">
                            <template x-for="warning in analysis.warnings">
                                <li class="text-xs text-yellow-700 dark:text-yellow-400 flex items-start gap-1">
                                    <svg width="14" height="14" class="mt-0.5 flex-shrink-0" style="width:14px;height:14px;min-width:14px" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                    </svg>
                                    <span x-text="warning"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </x-crm.card>

                <!-- Test de validación -->
                <x-crm.card>
                    <h3 class="text-sm font-semibold text-hando-text-light dark:text-hando-text-dark uppercase tracking-wider mb-2">
                        Probar validación
                    </h3>
                    <p class="text-xs text-hando-gray-500 dark:text-hando-gray-400 mb-3">
                        Pegá un texto de muestra y vé qué detecta el validador (sin gastar tokens de IA).
                    </p>

                    <textarea x-model="sampleOutput" rows="6"
                        placeholder="Pegá un texto generado para validarlo contra este prompt..."
                        class="w-full px-3 py-2 text-xs border border-hando-border-light dark:border-hando-border-dark rounded-hando bg-white dark:bg-hando-gray-800 mb-3"></textarea>

                    <button type="button" @click="runPreview()" :disabled="!sampleOutput.trim() || previewing"
                        class="w-full px-3 py-2 text-sm font-medium rounded-hando bg-purple-600 hover:bg-purple-700 disabled:bg-hando-gray-300 text-white transition-colors">
                        <span x-show="!previewing">Validar texto</span>
                        <span x-show="previewing">Validando…</span>
                    </button>

                    <!-- Resultado -->
                    <div x-show="previewResult" class="mt-4 pt-3 border-t border-hando-border-light dark:border-hando-border-dark">
                        <div class="flex items-center mb-2">
                            <span x-show="previewResult?.valid" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                ✓ VÁLIDO
                            </span>
                            <span x-show="!previewResult?.valid" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                ✗ INVÁLIDO
                            </span>
                            <span class="ml-2 text-xs text-hando-gray-500" x-text="`${previewResult?.metrics?.word_count || 0} palabras`"></span>
                        </div>

                        <div x-show="previewResult?.violations?.length > 0" class="space-y-1.5 max-h-60 overflow-y-auto">
                            <template x-for="v in previewResult?.violations || []">
                                <div class="text-xs p-2 rounded"
                                    :class="v.severity === 'critical' ? 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300' : 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-300'">
                                    <span class="font-semibold uppercase text-[10px]" x-text="v.severity"></span>
                                    <p x-text="v.detail"></p>
                                    <p x-show="v.evidence" class="mt-1 italic opacity-75" x-text="v.evidence"></p>
                                </div>
                            </template>
                        </div>

                        <p x-show="previewResult?.violations?.length === 0" class="text-xs text-green-700 dark:text-green-400 italic">
                            Sin violaciones. La salida cumple todas las reglas detectadas.
                        </p>
                    </div>
                </x-crm.card>

                <!-- Tips -->
                <div class="bg-hando-gray-50 dark:bg-hando-gray-800 rounded-hando p-4">
                    <h4 class="text-sm font-semibold text-hando-text-light dark:text-hando-text-dark mb-2">
                        Tips para prompts restrictivos
                    </h4>
                    <ul class="text-xs text-hando-gray-600 dark:text-hando-gray-400 space-y-1.5 list-disc list-inside">
                        <li>Usá <code>PALABRAS PROHIBIDAS:</code> seguido de coma-separadas para que el validador las detecte</li>
                        <li>Especificá <code>máximo N palabras</code> o <code>N a M palabras</code> para activar el límite</li>
                        <li>Activá <strong>Modo Estricto</strong> si querés ignorar ejemplos previos del entrenamiento</li>
                        <li>Si el cliente exige cumplimiento total, usá <code>gpt-4o</code> en vez de <code>gpt-4o-mini</code></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        function promptEditor(config) {
            return {
                prompt: config.initialPrompt,
                analysis: config.initialAnalysis,
                analyzing: false,
                sampleOutput: '',
                previewing: false,
                previewResult: null,

                async analyzeLive() {
                    this.analyzing = true;
                    try {
                        const res = await fetch(config.analyzeUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': config.csrf,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ prompt: this.prompt }),
                        });
                        if (res.ok) {
                            this.analysis = await res.json();
                        }
                    } catch (e) {
                        console.error('Error analizando prompt', e);
                    } finally {
                        this.analyzing = false;
                    }
                },

                async runPreview() {
                    if (!this.sampleOutput.trim()) return;
                    this.previewing = true;
                    this.previewResult = null;
                    try {
                        const res = await fetch(config.previewUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': config.csrf,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                sample_output: this.sampleOutput,
                                prompt_override: this.prompt,
                            }),
                        });
                        if (res.ok) {
                            this.previewResult = await res.json();
                        }
                    } catch (e) {
                        console.error('Error en preview', e);
                    } finally {
                        this.previewing = false;
                    }
                },
            };
        }
    </script>
</x-layouts.crm>
