<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-bold text-2xl leading-tight tracking-tight" style="color: #26221F;">
                    Gestión de Usuarios
                </h2>
                <p class="mt-1 text-sm" style="color: #A89F94;">
                    Administra y supervisa todos los usuarios del sistema
                </p>
            </div>
            <a href="{{ route('admin.users.create') }}"
               class="inline-flex items-center px-6 py-3 font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5" style="background-color: #26221F; color: #FFFFFF;">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Crear Usuario
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Success Message -->
            @if (session('success'))
                <div class="mb-6 relative overflow-hidden rounded-xl bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 border border-emerald-200 dark:border-emerald-800 shadow-lg animate-fade-in">
                    <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-gradient-to-b from-emerald-500 to-teal-500"></div>
                    <div class="flex items-center px-6 py-4">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-4 flex-1">
                            <p class="text-sm font-semibold text-emerald-900 dark:text-emerald-100">
                                {{ session('success') }}
                            </p>
                        </div>
                        <button onclick="this.parentElement.parentElement.remove()" class="flex-shrink-0 ml-4 text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-200 transition-colors">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            @endif

            <!-- Error Message -->
            @if (session('error'))
                <div class="mb-6 relative overflow-hidden rounded-xl bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border border-red-200 dark:border-red-800 shadow-lg animate-fade-in">
                    <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-gradient-to-b from-red-500 to-rose-500"></div>
                    <div class="flex items-center px-6 py-4">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-4 flex-1">
                            <p class="text-sm font-semibold text-red-900 dark:text-red-100">
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

            <!-- Table Card -->
            <div class="rounded-2xl shadow-xl overflow-hidden" style="background-color: #FFFFFF; border: 1px solid #A89F94;">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr style="background-color: #F5F2ED; border-bottom: 2px solid #A89F94;">
                                <th class="px-8 py-5 text-left text-xs font-bold uppercase tracking-wider" style="color: #26221F;">
                                    ID
                                </th>
                                <th class="px-8 py-5 text-left text-xs font-bold uppercase tracking-wider" style="color: #26221F;">
                                    Nombre
                                </th>
                                <th class="px-8 py-5 text-left text-xs font-bold uppercase tracking-wider" style="color: #26221F;">
                                    Email
                                </th>
                                <th class="px-8 py-5 text-left text-xs font-bold uppercase tracking-wider" style="color: #26221F;">
                                    Rol
                                </th>
                                <th class="px-8 py-5 text-left text-xs font-bold uppercase tracking-wider" style="color: #26221F;">
                                    Fecha Creación
                                </th>
                                <th class="px-8 py-5 text-right text-xs font-bold uppercase tracking-wider" style="color: #26221F;">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody style="background-color: #FFFFFF;">
                            @forelse ($users as $user)
                                <tr class="transition-colors duration-150" style="border-bottom: 1px solid #E5E0D8;" onmouseover="this.style.backgroundColor='#F5F2ED'" onmouseout="this.style.backgroundColor='#FFFFFF'">
                                    <td class="px-8 py-5 whitespace-nowrap">
                                        <span class="text-sm font-bold" style="color: #26221F;">
                                            #{{ $user->id }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-5 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center font-bold shadow-lg text-base" style="background-color: #26221F; color: #FFFFFF;">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-bold" style="color: #26221F;">
                                                    {{ $user->name }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5 whitespace-nowrap">
                                        <div class="text-sm font-medium" style="color: #26221F;">
                                            {{ $user->email }}
                                        </div>
                                    </td>
                                    <td class="px-8 py-5 whitespace-nowrap">
                                        @if ($user->is_admin)
                                            <span class="inline-flex items-center px-3 py-2 rounded-lg text-xs font-bold shadow-md" style="background-color: #26221F; color: #FFFFFF;">
                                                <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                                                </svg>
                                                Administrador
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-3 py-2 rounded-lg text-xs font-bold" style="background-color: #E5E0D8; color: #26221F;">
                                                <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                                </svg>
                                                Usuario
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-8 py-5 whitespace-nowrap">
                                        <div class="flex items-center text-sm font-medium" style="color: #26221F;">
                                            <svg class="w-4 h-4 mr-2" style="color: #A89F94;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            {{ $user->created_at->format('d/m/Y H:i') }}
                                        </div>
                                    </td>
                                    <td class="px-8 py-5 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('admin.users.show', $user) }}"
                                               class="inline-flex items-center px-3 py-2 font-bold rounded-lg transition-all duration-150 shadow hover:shadow-lg text-xs" style="background-color: #26221F; color: #FFFFFF;">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                Ver
                                            </a>
                                            <a href="{{ route('admin.users.edit', $user) }}"
                                               class="inline-flex items-center px-3 py-2 font-bold rounded-lg transition-all duration-150 shadow hover:shadow-lg text-xs" style="background-color: #A89F94; color: #FFFFFF;">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                Editar
                                            </a>
                                            @if ($user->id !== auth()->id())
                                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            onclick="return confirm('¿Estás seguro de eliminar este usuario?')"
                                                            class="inline-flex items-center px-3 py-2 font-bold rounded-lg transition-all duration-150 shadow hover:shadow-lg text-xs" style="background-color: #26221F; color: #FFFFFF; opacity: 0.7;">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                        Eliminar
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-8 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="w-16 h-16 text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                            <p class="text-lg font-semibold text-gray-600 dark:text-gray-400">
                                                No hay usuarios registrados
                                            </p>
                                            <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
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
                    <div class="px-8 py-5 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-fade-in {
            animation: fade-in 0.3s ease-out;
        }
    </style>
</x-app-layout>
