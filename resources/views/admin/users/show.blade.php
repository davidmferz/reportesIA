<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center">
            <a href="{{ route('admin.users.index') }}" class="mr-4 transition-colors" style="color: #A89F94;" onmouseover="this.style.color='#26221F'" onmouseout="this.style.color='#A89F94'">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h2 class="font-bold text-2xl leading-tight tracking-tight" style="color: #26221F;">
                    Detalles del Usuario
                </h2>
                <p class="mt-1 text-sm" style="color: #A89F94;">
                    Información completa de {{ $user->name }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Información del Usuario -->
            <div class="rounded-2xl shadow-xl overflow-hidden" style="background-color: #FFFFFF; border: 1px solid #A89F94;">
                <div class="p-8">
                    <h3 class="text-lg font-bold mb-6" style="color: #26221F;">Información del Usuario</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm font-medium mb-2" style="color: #A89F94;">ID</p>
                            <p class="font-bold" style="color: #26221F;">#{{ $user->id }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium mb-2" style="color: #A89F94;">Nombre</p>
                            <p class="font-bold" style="color: #26221F;">{{ $user->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium mb-2" style="color: #A89F94;">Email</p>
                            <p class="font-bold" style="color: #26221F;">{{ $user->email }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium mb-2" style="color: #A89F94;">Rol</p>
                            <p>
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
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium mb-2" style="color: #A89F94;">Fecha de Creación</p>
                            <p class="font-bold" style="color: #26221F;">{{ $user->created_at->format('d/m/Y H:i:s') }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium mb-2" style="color: #A89F94;">Última Actualización</p>
                            <p class="font-bold" style="color: #26221F;">{{ $user->updated_at->format('d/m/Y H:i:s') }}</p>
                        </div>
                    </div>
                    <div class="mt-8 flex gap-3 pt-6" style="border-top: 1px solid #A89F94;">
                        <a href="{{ route('admin.users.edit', $user) }}"
                           class="inline-flex items-center px-6 py-3 font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5"
                           style="background-color: #26221F; color: #FFFFFF;">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Editar Usuario
                        </a>
                        <a href="{{ route('admin.users.index') }}"
                           class="inline-flex items-center px-6 py-3 font-semibold rounded-xl transition-all duration-150 shadow hover:shadow-lg"
                           style="background-color: #F5F2ED; color: #26221F; border: 1px solid #A89F94;">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Volver al Listado
                        </a>
                    </div>
                </div>
            </div>

            <!-- Log de Auditoría -->
            <div class="rounded-2xl shadow-xl overflow-hidden" style="background-color: #FFFFFF; border: 1px solid #A89F94;">
                <div class="p-8">
                    <h3 class="text-lg font-bold mb-6" style="color: #26221F;">Registro de Actividad</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr style="background-color: #F5F2ED; border-bottom: 2px solid #A89F94;">
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider" style="color: #26221F;">
                                        Fecha
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider" style="color: #26221F;">
                                        Evento
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider" style="color: #26221F;">
                                        Descripción
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider" style="color: #26221F;">
                                        Usuario Responsable
                                    </th>
                                </tr>
                            </thead>
                            <tbody style="background-color: #FFFFFF;">
                                @forelse ($activities as $activity)
                                    <tr class="transition-colors duration-150" style="border-bottom: 1px solid #E5E0D8;" onmouseover="this.style.backgroundColor='#F5F2ED'" onmouseout="this.style.backgroundColor='#FFFFFF'">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" style="color: #26221F;">
                                            {{ $activity->created_at->format('d/m/Y H:i:s') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if ($activity->event === 'created')
                                                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold" style="background-color: #26221F; color: #FFFFFF;">
                                                    Creado
                                                </span>
                                            @elseif ($activity->event === 'updated')
                                                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold" style="background-color: #A89F94; color: #FFFFFF;">
                                                    Actualizado
                                                </span>
                                            @elseif ($activity->event === 'deleted')
                                                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold" style="background-color: #26221F; color: #FFFFFF; opacity: 0.7;">
                                                    Eliminado
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold" style="background-color: #E5E0D8; color: #26221F;">
                                                    {{ ucfirst($activity->event) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-sm font-medium" style="color: #26221F;">
                                            {{ $activity->description }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" style="color: #A89F94;">
                                            {{ $activity->causer ? $activity->causer->name : 'Sistema' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center">
                                            <p class="text-sm font-medium" style="color: #A89F94;">
                                                No hay actividad registrada
                                            </p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($activities->hasPages())
                        <div class="mt-6 pt-4" style="border-top: 1px solid #A89F94;">
                            {{ $activities->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
