<x-layouts.crm>
    <x-slot name="header">
        <div class="flex items-center">
            <a href="{{ route('admin.users.index') }}" class="mr-4 p-2 rounded-hando text-hando-gray-600 dark:text-hando-gray-400 hover:bg-hando-gray-100 dark:hover:bg-hando-gray-700 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-hando-text-light dark:text-hando-text-dark">Editar Usuario</h1>
                <p class="mt-1 text-sm text-hando-gray-500 dark:text-hando-gray-400">Actualiza la información de <span class="font-semibold">{{ $user->name }}</span></p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <x-crm.card>
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <x-hando-label for="name" value="Nombre Completo" :required="true" />
                    <x-hando-input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required>
                        <x-slot name="icon">
                            <svg class="h-5 w-5 text-hando-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </x-slot>
                    </x-hando-input>
                    @error('name')<p class="mt-2 text-sm text-hando-danger">{{ $message }}</p>@enderror
                </div>

                <div>
                    <x-hando-label for="email" value="Correo Electrónico" :required="true" />
                    <x-hando-input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required>
                        <x-slot name="icon">
                            <svg class="h-5 w-5 text-hando-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </x-slot>
                    </x-hando-input>
                    @error('email')<p class="mt-2 text-sm text-hando-danger">{{ $message }}</p>@enderror
                </div>

                <div>
                    <x-hando-label for="password" value="Nueva Contraseña" />
                    <p class="text-xs text-hando-gray-500 dark:text-hando-gray-400 mb-2">(dejar en blanco para mantener la actual)</p>
                    <x-hando-input id="password" name="password" type="password">
                        <x-slot name="icon">
                            <svg class="h-5 w-5 text-hando-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </x-slot>
                    </x-hando-input>
                    @error('password')<p class="mt-2 text-sm text-hando-danger">{{ $message }}</p>@enderror
                </div>

                <div>
                    <x-hando-label for="password_confirmation" value="Confirmar Nueva Contraseña" />
                    <x-hando-input id="password_confirmation" name="password_confirmation" type="password" />
                </div>

                <div class="pt-4 border-t border-hando-border-light dark:border-hando-border-dark">
                    <label class="flex items-start cursor-pointer">
                        <input type="checkbox" name="is_admin" value="1" {{ old('is_admin', $user->is_admin) ? 'checked' : '' }} class="mt-1 w-5 h-5 rounded border-hando-border-light text-hando-primary focus:ring-hando-primary">
                        <div class="ml-3">
                            <span class="text-sm font-medium text-hando-text-light dark:text-hando-text-dark">Otorgar privilegios de administrador</span>
                            <p class="text-xs text-hando-gray-500 dark:text-hando-gray-400 mt-1">Los administradores tienen acceso completo al sistema y pueden gestionar usuarios</p>
                        </div>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-6 border-t border-hando-border-light dark:border-hando-border-dark">
                    <a href="{{ route('admin.users.index') }}">
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
                        Actualizar Usuario
                    </x-hando-button>
                </div>
            </form>
        </x-crm.card>
    </div>
</x-layouts.crm>
