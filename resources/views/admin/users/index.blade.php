<x-layouts.crm>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-hando-text-light dark:text-hando-text-dark">Gestión de Usuarios</h1>
                <p class="mt-1 text-sm text-hando-gray-500 dark:text-hando-gray-400">Administra y supervisa todos los usuarios del sistema</p>
            </div>
            <a href="{{ route('admin.users.create') }}">
                <x-hando-button variant="primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Crear Usuario
                </x-hando-button>
            </a>
        </div>
    </x-slot>

    <div class="max-w-full mx-auto">
        <!-- Success Message -->
        @if (session('success'))
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

        <!-- Error Message -->
        @if (session('error'))
            <div class="mb-6 relative overflow-hidden rounded-hando bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 shadow-hando">
                <div class="flex items-center p-4">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-red-900 dark:text-red-100">
                            {{ session('error') }}
                        </p>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="flex-shrink-0 ml-4 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        <!-- Users Table Card -->
        <x-crm.card :padding="false">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-hando-gray-50 dark:bg-hando-gray-800 border-b border-hando-border-light dark:border-hando-border-dark">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-hando-gray-600 dark:text-hando-gray-400 uppercase tracking-wider">
                                ID
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-hando-gray-600 dark:text-hando-gray-400 uppercase tracking-wider">
                                Nombre
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-hando-gray-600 dark:text-hando-gray-400 uppercase tracking-wider">
                                Email
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-hando-gray-600 dark:text-hando-gray-400 uppercase tracking-wider">
                                Rol
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-hando-gray-600 dark:text-hando-gray-400 uppercase tracking-wider">
                                Fecha Creación
                            </th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-hando-gray-600 dark:text-hando-gray-400 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-hando-border-light dark:divide-hando-border-dark">
                        @forelse ($users as $user)
                            <tr class="hover:bg-hando-gray-50 dark:hover:bg-hando-gray-700 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-semibold text-hando-text-light dark:text-hando-text-dark">
                                        #{{ $user->id }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gradient-to-br from-hando-primary to-blue-600 flex items-center justify-center">
                                            <span class="text-white font-semibold text-sm">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-hando-text-light dark:text-hando-text-dark">
                                                {{ $user->name }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-hando-text-light dark:text-hando-text-dark">
                                        {{ $user->email }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
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
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center text-sm text-hando-gray-500 dark:text-hando-gray-400">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        {{ $user->created_at->format('d/m/Y H:i') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.users.show', $user) }}">
                                            <x-hando-button variant="secondary" size="sm">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                Ver
                                            </x-hando-button>
                                        </a>
                                        <a href="{{ route('admin.users.edit', $user) }}">
                                            <x-hando-button variant="primary" size="sm">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                Editar
                                            </x-hando-button>
                                        </a>
                                        @if ($user->id !== auth()->id())
                                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <x-hando-button
                                                    type="submit"
                                                    variant="danger"
                                                    size="sm"
                                                    onclick="return confirm('¿Estás seguro de eliminar este usuario?')"
                                                >
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                    Eliminar
                                                </x-hando-button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-16 h-16 text-hando-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        <p class="text-base font-semibold text-hando-gray-600 dark:text-hando-gray-400">
                                            No hay usuarios registrados
                                        </p>
                                        <p class="text-sm text-hando-gray-500 dark:text-hando-gray-500 mt-1">
                                            Comienza agregando tu primer usuario
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="px-6 py-4 bg-hando-gray-50 dark:bg-hando-gray-800 border-t border-hando-border-light dark:border-hando-border-dark">
                    {{ $users->links() }}
                </div>
            @endif
        </x-crm.card>
    </div>
</x-layouts.crm>
