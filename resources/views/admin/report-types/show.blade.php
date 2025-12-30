<x-layouts.crm>
    <x-slot name="header">
        <div class="flex items-center">
            <a href="{{ route('admin.report-types.index') }}" class="mr-4 p-2 rounded-hando text-hando-gray-600 dark:text-hando-gray-400 hover:bg-hando-gray-100 dark:hover:bg-hando-gray-700 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-hando-text-light dark:text-hando-text-dark">Detalles del Tipo de Reporte</h1>
                <p class="mt-1 text-sm text-hando-gray-500 dark:text-hando-gray-400">Información completa de {{ $reportType->nombre }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto">
        <x-crm.card title="Información del Tipo de Reporte">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm font-medium text-hando-gray-500 dark:text-hando-gray-400 mb-2">ID</p>
                    <p class="font-semibold text-hando-text-light dark:text-hando-text-dark">#{{ $reportType->id }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-hando-gray-500 dark:text-hando-gray-400 mb-2">Nombre</p>
                    <p class="font-semibold text-hando-text-light dark:text-hando-text-dark">{{ $reportType->nombre }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-hando-gray-500 dark:text-hando-gray-400 mb-2">Creado por</p>
                    <p class="font-semibold text-hando-text-light dark:text-hando-text-dark">
                        {{ $reportType->creator ? $reportType->creator->name : 'N/A' }}
                    </p>
                </div>
                <div>
                    <p class="text-sm font-medium text-hando-gray-500 dark:text-hando-gray-400 mb-2">Fecha de Creación</p>
                    <p class="font-semibold text-hando-text-light dark:text-hando-text-dark">{{ $reportType->created_at->format('d/m/Y H:i:s') }}</p>
                </div>
                @if($reportType->updater)
                <div>
                    <p class="text-sm font-medium text-hando-gray-500 dark:text-hando-gray-400 mb-2">Actualizado por</p>
                    <p class="font-semibold text-hando-text-light dark:text-hando-text-dark">{{ $reportType->updater->name }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-hando-gray-500 dark:text-hando-gray-400 mb-2">Última Actualización</p>
                    <p class="font-semibold text-hando-text-light dark:text-hando-text-dark">{{ $reportType->updated_at->format('d/m/Y H:i:s') }}</p>
                </div>
                @endif
                @if($reportType->deleted_at)
                <div>
                    <p class="text-sm font-medium text-hando-gray-500 dark:text-hando-gray-400 mb-2">Eliminado por</p>
                    <p class="font-semibold text-hando-text-light dark:text-hando-text-dark">
                        {{ $reportType->deleter ? $reportType->deleter->name : 'N/A' }}
                    </p>
                </div>
                <div>
                    <p class="text-sm font-medium text-hando-gray-500 dark:text-hando-gray-400 mb-2">Fecha de Eliminación</p>
                    <p class="font-semibold text-hando-text-light dark:text-hando-text-dark">{{ $reportType->deleted_at->format('d/m/Y H:i:s') }}</p>
                </div>
                @endif
            </div>
            <div class="mt-8 flex gap-3 pt-6 border-t border-hando-border-light dark:border-hando-border-dark">
                @if(Auth::user()->is_admin)
                <a href="{{ route('admin.report-types.edit', $reportType) }}">
                    <x-hando-button variant="primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Editar Tipo de Reporte
                    </x-hando-button>
                </a>
                @endif
                <a href="{{ route('admin.report-types.index') }}">
                    <x-hando-button variant="secondary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Volver al Listado
                    </x-hando-button>
                </a>
            </div>
        </x-crm.card>
    </div>
</x-layouts.crm>
