<x-layouts.crm>
    <x-slot name="header">
        <div class="flex items-center">
            <a href="{{ route('admin.users.index') }}" class="mr-4 p-2 rounded-hando text-hando-gray-600 dark:text-hando-gray-400 hover:bg-hando-gray-100 dark:hover:bg-hando-gray-700 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-hando-text-light dark:text-hando-text-dark">Detalles del Usuario</h1>
                <p class="mt-1 text-sm text-hando-gray-500 dark:text-hando-gray-400">Información completa de {{ $user->name }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto">
        <x-crm.card title="Información del Usuario">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm font-medium text-hando-gray-500 dark:text-hando-gray-400 mb-2">ID</p>
                    <p class="font-semibold text-hando-text-light dark:text-hando-text-dark">#{{ $user->id }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-hando-gray-500 dark:text-hando-gray-400 mb-2">Nombre</p>
                    <p class="font-semibold text-hando-text-light dark:text-hando-text-dark">{{ $user->name }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-hando-gray-500 dark:text-hando-gray-400 mb-2">Email</p>
                    <p class="font-semibold text-hando-text-light dark:text-hando-text-dark">{{ $user->email }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-hando-gray-500 dark:text-hando-gray-400 mb-2">Rol</p>
                    @if ($user->is_admin)
                        <span class="inline-flex items-center px-3 py-1 rounded-hando text-xs font-semibold bg-hando-primary text-white">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                            </svg>
                            Administrador
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-hando text-xs font-semibold bg-hando-gray-200 dark:bg-hando-gray-700 text-hando-text-light dark:text-hando-text-dark">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                            Usuario
                        </span>
                    @endif
                </div>
                <div>
                    <p class="text-sm font-medium text-hando-gray-500 dark:text-hando-gray-400 mb-2">Fecha de Creación</p>
                    <p class="font-semibold text-hando-text-light dark:text-hando-text-dark">{{ $user->created_at->format('d/m/Y H:i:s') }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-hando-gray-500 dark:text-hando-gray-400 mb-2">Última Actualización</p>
                    <p class="font-semibold text-hando-text-light dark:text-hando-text-dark">{{ $user->updated_at->format('d/m/Y H:i:s') }}</p>
                </div>
            </div>
            <div class="mt-8 flex gap-3 pt-6 border-t border-hando-border-light dark:border-hando-border-dark">
                <a href="{{ route('admin.users.edit', $user) }}">
                    <x-hando-button variant="primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Editar Usuario
                    </x-hando-button>
                </a>
                <a href="{{ route('admin.users.index') }}">
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
