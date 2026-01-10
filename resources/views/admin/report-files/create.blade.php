<x-layouts.crm>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.report-files.show', $reportType) }}" class="text-hando-gray-500 hover:text-hando-gray-700 dark:text-hando-gray-400 dark:hover:text-hando-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-hando-text-light dark:text-hando-text-dark">Subir Archivos</h1>
                <p class="mt-1 text-sm text-hando-gray-500 dark:text-hando-gray-400">{{ $reportType->nombre }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <!-- Indicador del tipo de reporte seleccionado - MEJORADO -->
        <div class="mb-8">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 dark:from-blue-700 dark:to-blue-800 rounded-xl shadow-2xl border-4 border-blue-400 dark:border-blue-500 transform hover:scale-[1.01] transition-transform">
                <div class="p-6">
                    <div class="flex flex-col space-y-4">
                        <!-- Título principal -->
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0 h-14 w-14 bg-white/30 rounded-xl flex items-center justify-center ring-4 ring-white/20">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-blue-100 uppercase tracking-widest mb-1">
                                     SUBIRÁS ARCHIVOS A:
                                </p>
                                <p class="text-3xl font-black text-blue-100 leading-tight">
                                    {{ $reportType->nombre }}
                                </p>
                            </div>
                        </div>

                        <!-- Badge con ID -->
                        <div class="flex items-center justify-between pt-3 border-t-2 border-white/20">
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-4 py-2 rounded-lg text-base font-bold bg-white/90 text-blue-700 shadow-md">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                    </svg>
                                    ID: {{ $reportType->id }}
                                </span>
                            </div>
                            <div class="text-xs font-semibold text-white/80 bg-white/10 px-3 py-1.5 rounded-lg">
                                Verifica que sea el tipo correcto
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <x-crm.card>
            <form action="{{ route('admin.report-files.store', $reportType) }}"
                  method="POST"
                  enctype="multipart/form-data"
                  x-data="{
                      capitulo: '',
                      archivosEntrada: [],
                      archivoSalida: null,
                      uploading: false
                  }"
                  @submit="uploading = true">
                @csrf

                <div class="space-y-6">
                    <!-- Información sobre formatos -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-hando p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-900 dark:text-blue-100">
                                    Debes especificar el capítulo, seleccionar archivos de entrada (uno o más) y un archivo de salida. Los formatos más comunes son: <strong>DOCX, VSDX, PDF, XLSB</strong>.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Capítulo Field -->
                    <div class="space-y-2">
                        <label for="capitulo" class="block text-sm font-medium text-hando-text-light dark:text-hando-text-dark">
                            Capítulo <span class="text-hando-danger">*</span>
                        </label>
                        <input
                            type="text"
                            name="capitulo"
                            id="capitulo"
                            required
                            maxlength="255"
                            x-model="capitulo"
                            class="block w-full rounded-hando border-hando-border-light dark:border-hando-border-dark bg-white dark:bg-hando-card-dark text-hando-text-light dark:text-hando-text-dark focus:border-hando-primary focus:ring-hando-primary sm:text-sm"
                            placeholder="Ej: Capítulo 1, Introducción, etc."
                        >
                        @error('capitulo')
                            <p class="mt-1 text-sm text-hando-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Archivos de Entrada Upload Area -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-hando-text-light dark:text-hando-text-dark">
                            Archivos de Entrada <span class="text-hando-danger">*</span>
                        </label>
                        <p class="text-xs text-hando-gray-500 dark:text-hando-gray-400 mb-2">
                            Selecciona uno o más archivos de entrada que serán procesados.
                        </p>
                        <div class="border-2 border-dashed border-hando-border-light dark:border-hando-border-dark rounded-hando p-8 text-center hover:border-hando-primary transition-colors">
                            <input
                                type="file"
                                name="archivos_entrada[]"
                                id="archivos_entrada"
                                multiple
                                required
                                x-on:change="archivosEntrada = Array.from($event.target.files)"
                                class="hidden"
                            >
                            <label for="archivos_entrada" class="cursor-pointer">
                                <svg class="mx-auto h-12 w-12 text-hando-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <p class="mt-2 text-sm text-hando-gray-600 dark:text-hando-gray-400">
                                    <span class="font-medium text-hando-primary hover:text-hando-primary-hover">Haz clic para seleccionar</span> o arrastra archivos aquí
                                </p>
                                <p class="mt-1 text-xs text-hando-gray-500 dark:text-hando-gray-400">
                                    Tamaño máximo: 50MB por archivo
                                </p>
                            </label>
                        </div>
                        @error('archivos_entrada.*')
                            <p class="mt-1 text-sm text-hando-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Lista de archivos de entrada seleccionados -->
                    <div x-show="archivosEntrada.length > 0" class="space-y-2">
                        <label class="block text-sm font-medium text-hando-text-light dark:text-hando-text-dark">
                            Archivos de entrada seleccionados (<span x-text="archivosEntrada.length"></span>)
                        </label>
                        <div class="bg-hando-gray-50 dark:bg-hando-gray-800 rounded-hando p-4 max-h-60 overflow-y-auto">
                            <ul class="space-y-2">
                                <template x-for="(file, index) in archivosEntrada" :key="index">
                                    <li class="flex items-center justify-between p-2 bg-white dark:bg-hando-card-dark rounded-hando">
                                        <div class="flex items-center space-x-3">
                                            <div class="flex-shrink-0 h-8 w-8 bg-blue-100 dark:bg-blue-900/30 rounded flex items-center justify-center">
                                                <svg class="w-4 h-4 text-hando-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-hando-text-light dark:text-hando-text-dark" x-text="file.name"></p>
                                                <p class="text-xs text-hando-gray-500 dark:text-hando-gray-400" x-text="formatFileSize(file.size)"></p>
                                            </div>
                                        </div>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>

                    <!-- Archivo de Salida Upload Area -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-hando-text-light dark:text-hando-text-dark">
                            Archivo de Salida <span class="text-hando-danger">*</span>
                        </label>
                        <p class="text-xs text-hando-gray-500 dark:text-hando-gray-400 mb-2">
                            Selecciona UN archivo que representa el resultado del procesamiento.
                        </p>
                        <div class="border-2 border-dashed border-green-300 dark:border-green-700 rounded-hando p-8 text-center hover:border-green-500 transition-colors bg-green-50 dark:bg-green-900/10">
                            <input
                                type="file"
                                name="archivo_salida"
                                id="archivo_salida"
                                required
                                x-on:change="archivoSalida = $event.target.files[0]"
                                class="hidden"
                            >
                            <label for="archivo_salida" class="cursor-pointer">
                                <svg class="mx-auto h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="mt-2 text-sm text-green-600 dark:text-green-400">
                                    <span class="font-medium hover:text-green-700">Haz clic para seleccionar</span> el archivo de salida
                                </p>
                                <p class="mt-1 text-xs text-hando-gray-500 dark:text-hando-gray-400">
                                    Solo 1 archivo - Tamaño máximo: 50MB
                                </p>
                            </label>
                        </div>
                        @error('archivo_salida')
                            <p class="mt-1 text-sm text-hando-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Preview for salida file -->
                    <div x-show="archivoSalida" class="space-y-2">
                        <label class="block text-sm font-medium text-hando-text-light dark:text-hando-text-dark">
                            Archivo de salida seleccionado
                        </label>
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-hando p-4 border border-green-200 dark:border-green-800">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0 h-8 w-8 bg-green-200 dark:bg-green-800 rounded flex items-center justify-center">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-hando-text-light dark:text-hando-text-dark" x-text="archivoSalida ? archivoSalida.name : ''"></p>
                                    <p class="text-xs text-hando-gray-500 dark:text-hando-gray-400" x-text="archivoSalida ? formatFileSize(archivoSalida.size) : ''"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Barra de progreso (visible solo cuando se está subiendo) -->
                    <div x-show="uploading" x-cloak class="space-y-3">
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-hando p-4">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <!-- Spinner animado -->
                                    <svg class="animate-spin h-6 w-6 text-hando-primary" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-blue-900 dark:text-blue-100">
                                        Subiendo archivos...
                                    </p>
                                    <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                                        Por favor espera. Esto puede tardar unos minutos según el tamaño de los archivos.
                                    </p>
                                </div>
                            </div>

                            <!-- Barra de progreso animada -->
                            <div class="mt-4 w-full bg-blue-200 dark:bg-blue-800 rounded-full h-2.5 overflow-hidden">
                                <div class="bg-hando-primary h-2.5 rounded-full animate-pulse" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-hando-border-light dark:border-hando-border-dark">
                        <a href="{{ route('admin.report-files.show', $reportType) }}" :class="{ 'pointer-events-none opacity-50': uploading }">
                            <button
                                type="button"
                                :disabled="uploading"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-hando shadow-sm text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-hando-primary transition-all duration-150"
                                @click="window.location.href='{{ route('admin.report-files.show', $reportType) }}'"
                            >
                                Cancelar
                            </button>
                        </a>
                        <button
                            type="submit"
                            :disabled="capitulo.length === 0 || archivosEntrada.length === 0 || !archivoSalida || uploading"
                            :class="{
                                'opacity-50 cursor-not-allowed': capitulo.length === 0 || archivosEntrada.length === 0 || !archivoSalida || uploading,
                                'hover:bg-blue-700': capitulo.length > 0 && archivosEntrada.length > 0 && archivoSalida && !uploading
                            }"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-hando shadow-sm text-white bg-hando-primary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-hando-primary transition-all duration-150"
                        >
                            <!-- Icono normal o spinner -->
                            <svg x-show="!uploading" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            <svg x-show="uploading" x-cloak class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <!-- Texto del botón -->
                            <span x-show="!uploading">
                                <span x-show="capitulo.length === 0 || archivosEntrada.length === 0 || !archivoSalida">Complete todos los campos</span>
                                <span x-show="capitulo.length > 0 && archivosEntrada.length > 0 && archivoSalida">Subir archivos</span>
                            </span>
                            <span x-show="uploading" x-cloak>Subiendo...</span>
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
