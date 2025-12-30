<x-layouts.crm>
    <x-slot name="header">
        <div class="flex items-center">
            <a href="{{ route('admin.report-types.index') }}" class="mr-4 p-2 rounded-hando text-hando-gray-600 dark:text-hando-gray-400 hover:bg-hando-gray-100 dark:hover:bg-hando-gray-700 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-hando-text-light dark:text-hando-text-dark">Crear Tipo de Reporte</h1>
                <p class="mt-1 text-sm text-hando-gray-500 dark:text-hando-gray-400">Completa el formulario para agregar un nuevo tipo de reporte</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <x-crm.card>
            <form method="POST" action="{{ route('admin.report-types.store') }}" class="space-y-6">
                @csrf

                <!-- Nombre -->
                <div>
                    <x-hando-label for="nombre" value="Nombre del Tipo de Reporte" :required="true" />
                    <x-hando-input
                        id="nombre"
                        name="nombre"
                        type="text"
                        value="{{ old('nombre') }}"
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Crear Tipo de Reporte
                    </x-hando-button>
                </div>
            </form>
        </x-crm.card>
    </div>
</x-layouts.crm>
