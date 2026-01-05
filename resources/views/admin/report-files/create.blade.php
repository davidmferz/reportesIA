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
            <form action="{{ route('admin.report-files.store', $reportType) }}" method="POST" enctype="multipart/form-data" x-data="{ files: [] }">
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
                                    Puedes subir múltiples archivos a la vez. Los formatos más comunes son: <strong>DOCX, VSDX, PDF, XLSB</strong>, pero no están limitados a estos.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Upload Area -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-hando-text-light dark:text-hando-text-dark">
                            Seleccionar Archivos <span class="text-hando-danger">*</span>
                        </label>
                        <div class="border-2 border-dashed border-hando-border-light dark:border-hando-border-dark rounded-hando p-8 text-center hover:border-hando-primary transition-colors">
                            <input
                                type="file"
                                name="archivos[]"
                                id="archivos"
                                multiple
                                required
                                @change="files = Array.from($event.target.files)"
                                class="hidden"
                            >
                            <label for="archivos" class="cursor-pointer">
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
                        @error('archivos.*')
                            <p class="mt-1 text-sm text-hando-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Lista de archivos seleccionados -->
                    <div x-show="files.length > 0" class="space-y-2">
                        <label class="block text-sm font-medium text-hando-text-light dark:text-hando-text-dark">
                            Archivos seleccionados (<span x-text="files.length"></span>)
                        </label>
                        <div class="bg-hando-gray-50 dark:bg-hando-gray-800 rounded-hando p-4 max-h-60 overflow-y-auto">
                            <ul class="space-y-2">
                                <template x-for="(file, index) in files" :key="index">
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

                    <!-- Botones de acción -->
                    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-hando-border-light dark:border-hando-border-dark">
                        <a href="{{ route('admin.report-files.show', $reportType) }}">
                            <x-hando-button variant="secondary" type="button">
                                Cancelar
                            </x-hando-button>
                        </a>
                        <x-hando-button variant="primary" type="submit">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            Subir Archivos
                        </x-hando-button>
                    </div>
                </div>
            </form>
        </x-crm.card>
    </div>

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
