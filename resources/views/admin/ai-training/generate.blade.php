<x-layouts.crm>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.ai-training.show', $reportType) }}" class="text-hando-gray-500 hover:text-hando-gray-700 dark:text-hando-gray-400 dark:hover:text-hando-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-hando-text-light dark:text-hando-text-dark">Generar Documento</h1>
                    <p class="mt-1 text-sm text-hando-gray-500 dark:text-hando-gray-400">{{ $reportType->nombre }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    {{ $training->examples_count }} ejemplos entrenados
                </span>
                <span class="text-xs text-green-600 dark:text-green-400 font-medium">IA lista para generar</span>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        {{-- Alerta de estado de OpenAI --}}
        @if(isset($openaiStatus) && !$openaiStatus['ok'])
        <div class="mb-6 relative overflow-hidden rounded-hando bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 shadow-hando">
            <div class="flex items-center p-4">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-bold text-red-900 dark:text-red-100">API de OpenAI no disponible</h3>
                    <p class="text-sm text-red-800 dark:text-red-200 mt-1">{{ $openaiStatus['message'] }}</p>
                    @if(($openaiStatus['code'] ?? '') === 'insufficient_quota')
                        <p class="text-xs text-red-600 dark:text-red-300 mt-2">
                            Para resolver esto, ve a <a href="https://platform.openai.com/account/billing" target="_blank" class="underline font-semibold">platform.openai.com/account/billing</a> y agrega fondos a tu cuenta.
                        </p>
                    @endif
                </div>
            </div>
        </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-hando">
                <div class="flex items-start gap-3">
                    <svg width="20" height="20" class="flex-shrink-0 mt-0.5 text-red-600 dark:text-red-400" style="width:20px;height:20px;min-width:20px" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-red-800 dark:text-red-200">Error al generar</p>
                        <p class="text-sm text-red-700 dark:text-red-300 mt-1">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-hando">
                <p class="text-sm text-green-800 dark:text-green-200">{{ session('success') }}</p>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-hando">
                <p class="text-sm font-semibold text-red-800 dark:text-red-200 mb-1">Revisá los siguientes campos:</p>
                <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-300">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <x-crm.card>
            <form action="{{ route('admin.ai-training.generate.store', $reportType) }}"
                  method="POST"
                  enctype="multipart/form-data"
                  id="generateForm"
                  x-data="{
                      chapter_id: '',
                      archivos: [],
                      generating: false,
                      submitForm() {
                          if (!this.chapter_id) {
                              alert('Debes seleccionar un capítulo');
                              return false;
                          }
                          if (this.archivos.length === 0) {
                              alert('Debes seleccionar al menos un archivo');
                              return false;
                          }
                          this.generating = true;
                          return true;
                      }
                  }"
                  @submit.prevent="if(submitForm()) $el.submit()">
                @csrf

                <div class="space-y-6">
                    <!-- Información -->
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-hando p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-900 dark:text-green-100">
                                    Sube archivos de entrada y la IA generará automáticamente la salida basándose en los <strong>{{ $training->examples_count }} ejemplos</strong> de entrenamiento.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Selección de Capítulo -->
                    <div class="space-y-2">
                        <label for="chapter_id" class="block text-sm font-medium text-hando-text-light dark:text-hando-text-dark">
                            Capítulo <span class="text-hando-danger">*</span>
                        </label>
                        <p class="text-xs text-hando-gray-500 dark:text-hando-gray-400 mb-2">
                            Selecciona el capítulo para el cual la IA generará el documento.
                        </p>
                        <select
                            name="chapter_id"
                            id="chapter_id"
                            required
                            x-model="chapter_id"
                            class="block w-full rounded-hando border-hando-border-light dark:border-hando-border-dark bg-white dark:bg-hando-card-dark text-hando-text-light dark:text-hando-text-dark focus:border-hando-primary focus:ring-hando-primary sm:text-sm"
                        >
                            <option value="">-- Selecciona un capítulo --</option>
                            @foreach($chapters as $chapter)
                                <option value="{{ $chapter->id }}">
                                    {{ $chapter->orden }}. {{ $chapter->nombre }}
                                    @if($chapter->descripcion)
                                        - {{ Str::limit($chapter->descripcion, 50) }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @if($chapters->isEmpty())
                            <p class="mt-2 text-sm text-yellow-600 dark:text-yellow-400">
                                <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                No hay capítulos disponibles para este tipo de reporte.
                                <a href="{{ route('admin.chapters.index', $reportType) }}" class="underline hover:no-underline">Crear capítulos</a>
                            </p>
                        @endif
                        @error('chapter_id')
                            <p class="mt-1 text-sm text-hando-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Configuración sugerida por el catálogo para este tipo de documento.
                         Es orientación para quien redacta: no altera lo que produce la IA. --}}
                    @if(!empty($configuracionSugerida))
                    <div class="rounded-hando border border-hando-border-light dark:border-hando-border-dark p-4">
                        <p class="text-sm font-semibold text-hando-text-light dark:text-hando-text-dark">
                            Configuración sugerida del catálogo
                        </p>
                        <p class="mt-1 text-xs text-hando-gray-500 dark:text-hando-gray-400">
                            {{ $reportType->catalogServicePath() }} — orientación del Excel del generador, no altera la salida de la IA.
                        </p>
                        <dl class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                            @foreach($configuracionSugerida as $etiqueta => $valor)
                                <div class="bg-hando-gray-50 dark:bg-hando-gray-800 rounded-hando p-3">
                                    <dt class="text-xs text-hando-gray-500 dark:text-hando-gray-400">{{ $etiqueta }}</dt>
                                    <dd class="mt-1 text-sm font-semibold text-hando-text-light dark:text-hando-text-dark">{{ $valor }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                    @endif

                    <!-- Clasificación del proyecto -->
                    <x-catalog-selector
                        :tree="$catalogTree"
                        :selected="$catalogSelection"
                        :baseline="$dominioDeclarado"
                        description="Se precarga con la clasificación del tipo de reporte y podés ajustarla para esta generación. Hoy solo se guarda: no altera lo que produce la IA."
                    />

                    <!-- Archivos de Entrada -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-hando-text-light dark:text-hando-text-dark">
                            Archivos de Entrada <span class="text-hando-danger">*</span>
                        </label>
                        <p class="text-xs text-hando-gray-500 dark:text-hando-gray-400 mb-2">
                            Sube los archivos que la IA debe procesar para generar la salida.
                        </p>
                        <div class="border-2 border-dashed border-green-300 dark:border-green-700 rounded-hando p-6 text-center hover:border-green-500 transition-colors bg-green-50 dark:bg-green-900/10">
                            <input
                                type="file"
                                name="archivos[]"
                                id="archivos"
                                multiple
                                required
                                @change="archivos = Array.from($event.target.files)"
                                class="hidden"
                            >
                            <label for="archivos" class="cursor-pointer">
                                <svg class="mx-auto h-10 w-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <p class="mt-2 text-sm text-green-600 dark:text-green-400">
                                    <span class="font-medium hover:text-green-700">Haz clic para seleccionar</span> o arrastra archivos aquí
                                </p>
                                <p class="mt-1 text-xs text-hando-gray-500 dark:text-hando-gray-400">
                                    Formatos: PDF, DOCX, XLSX, TXT | Máx 50MB
                                </p>
                            </label>
                        </div>
                        @error('archivos.*')
                            <p class="mt-1 text-sm text-hando-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Lista de archivos seleccionados -->
                    <div x-show="archivos.length > 0" x-transition class="space-y-2">
                        <label class="block text-sm font-medium text-hando-text-light dark:text-hando-text-dark">
                            Archivos seleccionados (<span x-text="archivos.length"></span>)
                        </label>
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-hando p-3 border border-green-200 dark:border-green-800">
                            <ul class="space-y-2">
                                <template x-for="(file, index) in archivos" :key="index">
                                    <li class="flex items-center p-2 bg-white dark:bg-hando-card-dark rounded">
                                        <div class="flex-shrink-0 h-8 w-8 bg-green-200 dark:bg-green-800 rounded flex items-center justify-center mr-3">
                                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-hando-text-light dark:text-hando-text-dark" x-text="file.name"></p>
                                            <p class="text-xs text-hando-gray-500" x-text="formatFileSize(file.size)"></p>
                                        </div>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>

                    <!-- Barra de progreso -->
                    <div x-show="generating" x-cloak x-transition class="space-y-3">
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-hando p-4">
                            <div class="flex items-center space-x-3">
                                <svg class="animate-spin h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-bold text-green-900 dark:text-green-100">Generando documento con IA...</p>
                                    <p class="text-xs text-green-700 dark:text-green-300">Esto puede tardar varios minutos.</p>
                                </div>
                            </div>
                            <div class="mt-3 w-full bg-green-200 dark:bg-green-800 rounded-full h-2 overflow-hidden">
                                <div class="bg-green-600 h-2 rounded-full animate-pulse" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="flex items-center justify-end space-x-3 pt-6 border-t border-hando-border-light dark:border-hando-border-dark">
                        <a href="{{ route('admin.ai-training.show', $reportType) }}"
                           x-show="!generating"
                           class="inline-flex items-center px-4 py-2.5 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-hando shadow-sm text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-all">
                            Cancelar
                        </a>

                        <button
                            type="submit"
                            :disabled="!chapter_id || archivos.length === 0 || generating"
                            class="inline-flex items-center px-6 py-2.5 border border-transparent text-sm font-semibold rounded-hando shadow-sm text-white transition-all bg-gray-400"
                            :class="chapter_id && archivos.length > 0 && !generating ? 'bg-green-600 hover:bg-green-700 cursor-pointer' : 'bg-gray-400 cursor-not-allowed'"
                        >
                            <svg x-show="!generating" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <svg x-show="generating" x-cloak class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-show="!generating" x-text="chapter_id && archivos.length > 0 ? 'Generar con IA' : (!chapter_id ? 'Selecciona un capítulo' : 'Selecciona archivos')">Selecciona un capítulo</span>
                            <span x-show="generating" x-cloak>Generando...</span>
                        </button>
                    </div>
                </div>
            </form>
        </x-crm.card>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>

    <script>
        function formatFileSize(bytes) {
            if (bytes >= 1073741824) {
                return (bytes / 1073741824).toFixed(2) + ' GB';
            } else if (bytes >= 1048576) {
                return (bytes / 1048576).toFixed(2) + ' MB';
            } else if (bytes >= 1024) {
                return (bytes / 1024).toFixed(2) + ' KB';
            } else {
                return bytes + ' bytes';
            }
        }
    </script>
</x-layouts.crm>
