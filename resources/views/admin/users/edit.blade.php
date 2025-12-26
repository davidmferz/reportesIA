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
                    Editar Usuario
                </h2>
                <p class="mt-1 text-sm" style="color: #A89F94;">
                    Actualiza la información de <span class="font-semibold">{{ $user->name }}</span>
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl shadow-xl overflow-hidden" style="background-color: #FFFFFF; border: 1px solid #A89F94;">
                <div class="p-8">
                    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <!-- Nombre -->
                        <div>
                            <label for="name" class="flex items-center text-sm font-semibold mb-2" style="color: #26221F;">
                                <svg class="w-4 h-4 mr-2" style="color: #A89F94;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Nombre Completo
                            </label>
                            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                                   class="w-full px-4 py-3 rounded-xl border-2 transition-all duration-200 outline-none font-medium" style="border-color: #A89F94; background-color: #F5F2ED; color: #26221F;"
                                   onfocus="this.style.borderColor='#26221F'; this.style.boxShadow='0 0 0 4px rgba(38, 34, 31, 0.1)'"
                                   onblur="this.style.borderColor='#A89F94'; this.style.boxShadow='none'"
                                   placeholder="Ingresa el nombre completo del usuario">
                            @error('name')
                                <p class="mt-2 text-sm flex items-center" style="color: #26221F;">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="flex items-center text-sm font-semibold mb-2" style="color: #26221F;">
                                <svg class="w-4 h-4 mr-2" style="color: #A89F94;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                Correo Electrónico
                            </label>
                            <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                                   class="w-full px-4 py-3 rounded-xl border-2 transition-all duration-200 outline-none font-medium" style="border-color: #A89F94; background-color: #F5F2ED; color: #26221F;"
                                   onfocus="this.style.borderColor='#26221F'; this.style.boxShadow='0 0 0 4px rgba(38, 34, 31, 0.1)'"
                                   onblur="this.style.borderColor='#A89F94'; this.style.boxShadow='none'"
                                   placeholder="usuario@ejemplo.com">
                            @error('email')
                                <p class="mt-2 text-sm flex items-center" style="color: #26221F;">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Contraseña -->
                        <div>
                            <label for="password" class="flex items-center text-sm font-semibold mb-2" style="color: #26221F;">
                                <svg class="w-4 h-4 mr-2" style="color: #A89F94;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                Nueva Contraseña
                                <span class="ml-2 text-xs font-normal" style="color: #A89F94;">(dejar en blanco para mantener la actual)</span>
                            </label>
                            <input type="password" name="password" id="password"
                                   class="w-full px-4 py-3 rounded-xl border-2 transition-all duration-200 outline-none font-medium" style="border-color: #A89F94; background-color: #F5F2ED; color: #26221F;"
                                   onfocus="this.style.borderColor='#26221F'; this.style.boxShadow='0 0 0 4px rgba(38, 34, 31, 0.1)'"
                                   onblur="this.style.borderColor='#A89F94'; this.style.boxShadow='none'"
                                   placeholder="Mínimo 8 caracteres">
                            @error('password')
                                <p class="mt-2 text-sm flex items-center" style="color: #26221F;">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Confirmar Contraseña -->
                        <div>
                            <label for="password_confirmation" class="flex items-center text-sm font-semibold mb-2" style="color: #26221F;">
                                <svg class="w-4 h-4 mr-2" style="color: #A89F94;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Confirmar Nueva Contraseña
                            </label>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                   class="w-full px-4 py-3 rounded-xl border-2 transition-all duration-200 outline-none font-medium" style="border-color: #A89F94; background-color: #F5F2ED; color: #26221F;"
                                   onfocus="this.style.borderColor='#26221F'; this.style.boxShadow='0 0 0 4px rgba(38, 34, 31, 0.1)'"
                                   onblur="this.style.borderColor='#A89F94'; this.style.boxShadow='none'"
                                   placeholder="Repite la contraseña">
                        </div>

                        <!-- Es Administrador -->
                        <div class="pt-4" style="border-top: 1px solid #A89F94;">
                            <label class="flex items-start cursor-pointer group">
                                <div class="relative flex items-center">
                                    <input type="checkbox" name="is_admin" value="1" {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}
                                           class="w-5 h-5 rounded-lg border-2 transition-all" style="border-color: #A89F94; color: #26221F;"
                                           onfocus="this.style.boxShadow='0 0 0 4px rgba(38, 34, 31, 0.1)'"
                                           onblur="this.style.boxShadow='none'">
                                </div>
                                <div class="ml-3">
                                    <span class="text-sm font-semibold transition-colors" style="color: #26221F;">
                                        Otorgar privilegios de administrador
                                    </span>
                                    <p class="text-xs mt-1" style="color: #A89F94;">
                                        Los administradores tienen acceso completo al sistema y pueden gestionar usuarios
                                    </p>
                                </div>
                            </label>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="flex items-center justify-end gap-4 pt-6" style="border-top: 1px solid #A89F94;">
                            <a href="{{ route('admin.users.index') }}"
                               class="inline-flex items-center px-6 py-3 font-semibold rounded-xl transition-all duration-150 shadow hover:shadow-lg" style="background-color: #F5F2ED; color: #26221F; border: 1px solid #A89F94;">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Cancelar
                            </a>
                            <button type="submit"
                                    class="inline-flex items-center px-6 py-3 font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5" style="background-color: #26221F; color: #FFFFFF;">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Actualizar Usuario
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
