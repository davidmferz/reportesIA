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
                    <h1 class="text-2xl font-bold text-hando-text-light dark:text-hando-text-dark">Historial de Generaciones</h1>
                    <p class="mt-1 text-sm text-hando-gray-500 dark:text-hando-gray-400">{{ $reportType->nombre }}</p>
                </div>
            </div>
            @if($training && $training->status === 'ready')
                <a href="{{ route('admin.ai-training.generate.create', $reportType) }}">
                    <x-hando-button variant="primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Nueva Generación
                    </x-hando-button>
                </a>
            @endif
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
                    <p class="text-sm font-medium text-green-900 dark:text-green-100">{{ session('success') }}</p>
                </div>
            </div>
        </div>
        @endif

        <x-crm.card :padding="false">
            @if($generations instanceof \Illuminate\Pagination\LengthAwarePaginator && $generations->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-hando-border-light dark:divide-hando-border-dark">
                        <thead class="bg-hando-gray-50 dark:bg-hando-gray-800">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-hando-gray-500 dark:text-hando-gray-400 uppercase tracking-wider">
                                    Capítulo
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-hando-gray-500 dark:text-hando-gray-400 uppercase tracking-wider">
                                    Usuario
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-hando-gray-500 dark:text-hando-gray-400 uppercase tracking-wider">
                                    Estado
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-hando-gray-500 dark:text-hando-gray-400 uppercase tracking-wider">
                                    Tokens
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-hando-gray-500 dark:text-hando-gray-400 uppercase tracking-wider">
                                    Fecha
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-hando-gray-500 dark:text-hando-gray-400 uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-hando-card-dark divide-y divide-hando-border-light dark:divide-hando-border-dark">
                            @foreach($generations as $generation)
                            <tr class="hover:bg-hando-gray-50 dark:hover:bg-hando-gray-800 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 bg-purple-100 dark:bg-purple-900/30 rounded flex items-center justify-center">
                                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <span class="font-medium text-hando-text-light dark:text-hando-text-dark">
                                                @if($generation->chapter)
                                                    {{ $generation->chapter->orden }}. {{ $generation->chapter->nombre }}
                                                @else
                                                    {{ $generation->titulo ?? 'Sin capítulo' }}
                                                @endif
                                            </span>
                                            @if($generation->chapter && $generation->titulo)
                                                <p class="text-xs text-hando-gray-500">{{ $generation->titulo }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-hando-gray-500 dark:text-hando-gray-400">
                                    {{ $generation->user->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @php $badge = $generation->status_badge; @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($badge['color'] === 'green') bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300
                                        @elseif($badge['color'] === 'yellow') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300
                                        @elseif($badge['color'] === 'red') bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300
                                        @else bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300
                                        @endif">
                                        {{ $badge['text'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-hando-gray-500 dark:text-hando-gray-400">
                                    {{ $generation->total_tokens ? number_format($generation->total_tokens) : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-hando-gray-500 dark:text-hando-gray-400">
                                    {{ $generation->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <a href="{{ route('admin.ai-training.generation.show', [$reportType, $generation]) }}" class="text-hando-primary hover:text-hando-primary-hover">
                                        Ver
                                    </a>
                                    @if($generation->isCompleted())
                                        <a href="{{ route('admin.ai-training.generation.download', [$reportType, $generation]) }}" class="text-green-600 hover:text-green-700">
                                            Descargar
                                        </a>
                                    @endif
                                    <form action="{{ route('admin.ai-training.generation.destroy', [$reportType, $generation]) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Eliminar esta generación?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-hando-danger hover:text-red-700">
                                            Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($generations->hasPages())
                    <div class="px-6 py-4 border-t border-hando-border-light dark:border-hando-border-dark">
                        {{ $generations->links() }}
                    </div>
                @endif
            @else
                <div class="flex flex-col items-center justify-center py-12">
                    <svg class="w-12 h-12 text-hando-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <p class="text-hando-gray-500 dark:text-hando-gray-400 text-sm mb-4">No hay generaciones todavía</p>
                    @if($training && $training->status === 'ready')
                        <a href="{{ route('admin.ai-training.generate.create', $reportType) }}">
                            <x-hando-button variant="primary" size="sm">
                                Crear primera generación
                            </x-hando-button>
                        </a>
                    @else
                        <a href="{{ route('admin.ai-training.show', $reportType) }}">
                            <x-hando-button variant="secondary" size="sm">
                                Entrenar IA primero
                            </x-hando-button>
                        </a>
                    @endif
                </div>
            @endif
        </x-crm.card>
    </div>
</x-layouts.crm>
