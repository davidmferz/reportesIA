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

    <div class="max-w-4xl mx-auto">
        <!-- Información sobre el prompt -->
        <div class="mb-6 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 border border-purple-200 dark:border-purple-800 rounded-hando p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-semibold text-purple-900 dark:text-purple-100">¿Qué es el Prompt?</h3>
                    <p class="mt-1 text-sm text-purple-800 dark:text-purple-200">
                        El prompt son las <strong>instrucciones personalizadas</strong> que la IA seguirá al generar documentos de este tipo de reporte. 
                        Puedes especificar el estilo, formato, tono, secciones requeridas, o cualquier indicación especial.
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
                <button onclick="this.parentElement.parentElement.remove()" class="flex-shrink-0 ml-4 text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        @endif

        <x-crm.card>
            <form method="POST" action="{{ route('admin.report-files.update-prompt', $reportType) }}" class="space-y-6">
                @csrf
                @method('PATCH')

                <!-- Prompt -->
                <div>
                    <x-hando-label for="prompt" value="Prompt para la IA" />
                    <p class="text-xs text-hando-gray-500 dark:text-hando-gray-400 mb-2">
                        Escribe las instrucciones que la IA debe seguir al generar documentos de este tipo.
                    </p>
                    <textarea
                        id="prompt"
                        name="prompt"
                        rows="12"
                        placeholder="Ejemplo:
- Genera el reporte en formato formal y profesional
- Incluye siempre un resumen ejecutivo al inicio
- Usa tablas para presentar datos numéricos
- El tono debe ser objetivo y técnico
- Incluye conclusiones y recomendaciones al final"
                        class="w-full px-4 py-3 text-sm border border-hando-border-light dark:border-hando-border-dark rounded-hando bg-white dark:bg-hando-gray-800 text-hando-text-light dark:text-hando-text-dark placeholder-hando-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 resize-y transition-all"
                    >{{ old('prompt', $reportType->prompt) }}</textarea>
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
                            <span class="text-hando-gray-500 dark:text-hando-gray-400">Fecha de creación:</span>
                            <span class="ml-2 text-hando-text-light dark:text-hando-text-dark font-medium">
                                {{ $reportType->created_at->format('d/m/Y H:i') }}
                            </span>
                        </div>
                        @if($reportType->updater)
                        <div>
                            <span class="text-hando-gray-500 dark:text-hando-gray-400">Actualizado por:</span>
                            <span class="ml-2 text-hando-text-light dark:text-hando-text-dark font-medium">
                                {{ $reportType->updater->name }}
                            </span>
                        </div>
                        <div>
                            <span class="text-hando-gray-500 dark:text-hando-gray-400">Última actualización:</span>
                            <span class="ml-2 text-hando-text-light dark:text-hando-text-dark font-medium">
                                {{ $reportType->updated_at->format('d/m/Y H:i') }}
                            </span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="flex items-center justify-end gap-3 pt-6 border-t border-hando-border-light dark:border-hando-border-dark">
                    <a href="{{ route('admin.report-files.index') }}">
                        <x-hando-button variant="secondary" type="button">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Cancelar
                        </x-hando-button>
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

        <!-- Tips adicionales -->
        <div class="mt-6 bg-hando-gray-50 dark:bg-hando-gray-800 rounded-hando p-4">
            <h4 class="text-sm font-semibold text-hando-text-light dark:text-hando-text-dark mb-2">
                💡 Tips para escribir un buen prompt:
            </h4>
            <ul class="text-sm text-hando-gray-600 dark:text-hando-gray-400 space-y-1 list-disc list-inside">
                <li>Sé específico sobre el formato de salida deseado</li>
                <li>Indica el tono y estilo del documento (formal, técnico, ejecutivo)</li>
                <li>Menciona las secciones que debe incluir el reporte</li>
                <li>Especifica si hay datos que deben calcularse o resumirse</li>
                <li>Indica el idioma y cualquier terminología específica</li>
            </ul>
        </div>
    </div>
</x-layouts.crm>
