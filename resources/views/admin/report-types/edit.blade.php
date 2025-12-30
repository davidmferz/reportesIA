<x-layouts.crm>
    <x-slot name="header">
        <div class="flex items-center">
            <a href="{{ route('admin.report-types.index') }}" class="mr-4 p-2 rounded-hando text-hando-gray-600 dark:text-hando-gray-400 hover:bg-hando-gray-100 dark:hover:bg-hando-gray-700 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-hando-text-light dark:text-hando-text-dark">Editar Tipo de Reporte</h1>
                <p class="mt-1 text-sm text-hando-gray-500 dark:text-hando-gray-400">Actualiza la información de <span class="font-semibold">{{ $reportType->nombre }}</span></p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <x-crm.card>
            <form method="POST" action="{{ route('admin.report-types.update', $reportType) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Nombre -->
                <div>
                    <x-hando-label for="nombre" value="Nombre del Tipo de Reporte" :required="true" />
                    <x-hando-input
                        id="nombre"
                        name="nombre"
                        type="text"
                        value="{{ old('nombre', $reportType->nombre) }}"
                        required
                        placeholder="Ej: Reporte Mensual de Ventas"
                    >
                        <x-slot name="icon">
                            <svg class="h-5 w-5 text-hando-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </x-slot>
                    </x-hando-input>
                    @error('nombre')
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
                    <a href="{{ route('admin.report-types.index') }}">
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
                        Actualizar Tipo de Reporte
                    </x-hando-button>
                </div>
            </form>
        </x-crm.card>
    </div>
</x-layouts.crm>
