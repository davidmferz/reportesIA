<x-layouts.crm>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-hando-text-light dark:text-hando-text-dark">Gestión de Archivos</h1>
                <p class="mt-1 text-sm text-hando-gray-500 dark:text-hando-gray-400">Administra archivos por tipo de reporte</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-full mx-auto">
        @if(session('success'))
        <div class="mb-6 relative overflow-hidden rounded-hando bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 shadow-hando">
            <div class="flex items-center p-4">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-green-900 dark:text-green-100">
                        {{ session('success') }}
                    </p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="flex-shrink-0 ml-4 text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        @endif

        <x-crm.card :padding="false">
            <div class="overflow-x-auto">
                <table class="w-full table-fixed divide-y divide-hando-border-light dark:divide-hando-border-dark">
                    <thead class="bg-hando-gray-50 dark:bg-hando-gray-800">
                        <tr>
                            <th scope="col" class="w-[10%] px-6 py-3 text-left text-xs font-medium text-hando-gray-500 dark:text-hando-gray-400 uppercase tracking-wider">
                                ID
                            </th>
                            <th scope="col" class="w-[40%] px-6 py-3 text-left text-xs font-medium text-hando-gray-500 dark:text-hando-gray-400 uppercase tracking-wider">
                                Tipo de Reporte
                            </th>
                            <th scope="col" class="w-[20%] px-6 py-3 text-center text-xs font-medium text-hando-gray-500 dark:text-hando-gray-400 uppercase tracking-wider">
                                Archivos
                            </th>
                            <th scope="col" class="w-[30%] px-6 py-3 text-left text-xs font-medium text-hando-gray-500 dark:text-hando-gray-400 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-hando-card-dark divide-y divide-hando-border-light dark:divide-hando-border-dark">
                        @forelse($reportTypes as $reportType)
                        <tr class="hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-hando-text-light dark:text-hando-text-dark">
                                #{{ $reportType->id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-blue-100 dark:bg-blue-900/30 rounded-hando flex items-center justify-center group-hover:bg-blue-200 dark:group-hover:bg-blue-800 transition-colors">
                                        <svg class="w-5 h-5 text-hando-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <span class="ml-3 font-semibold text-hando-text-light dark:text-hando-text-dark group-hover:text-hando-primary transition-colors">
                                        {{ $reportType->nombre }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($reportType->files_count > 0)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-hando-primary">
                                        {{ $reportType->files_count }} {{ $reportType->files_count === 1 ? 'archivo' : 'archivos' }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-hando-gray-100 dark:bg-hando-gray-700 text-hando-gray-500 dark:text-hando-gray-400">
                                        Sin archivos
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <a href="{{ route('admin.report-files.show', $reportType) }}" class="text-hando-primary hover:text-hando-primary-hover transition-colors">
                                    Ver Archivos
                                </a>
                                <a href="{{ route('admin.report-files.create', $reportType) }}"
                                   onclick="return confirm('Vas a subir archivos para:\n\n{{ $reportType->nombre }} (ID: #{{ $reportType->id }})\n\n¿Deseas continuar?')"
                                   class="text-green-600 hover:text-green-700 transition-colors">
                                    Subir Archivo
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-hando-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="text-hando-gray-500 dark:text-hando-gray-400 text-sm">No hay tipos de reportes disponibles</p>
                                    <a href="{{ route('admin.report-types.index') }}" class="mt-4">
                                        <x-hando-button variant="primary" size="sm">
                                            Ir a Tipos de Reportes
                                        </x-hando-button>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-crm.card>
    </div>
</x-layouts.crm>
