<x-layouts.crm>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-hando-text-light dark:text-hando-text-dark">Dashboard</h1>
                <p class="mt-1 text-sm text-hando-gray-500 dark:text-hando-gray-400">Bienvenido, {{ Auth::user()->name }}!</p>
            </div>
            @if(Auth::user()->is_admin)
            <a href="{{ route('admin.users.create') }}">
                <x-hando-button variant="primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Crear Usuario
                </x-hando-button>
            </a>
            @endif
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-6">
        @php
            $totalUsers = \App\Models\User::count();
            $adminUsers = \App\Models\User::where('is_admin', true)->count();
            $regularUsers = \App\Models\User::where('is_admin', false)->count();
            $recentUsers = \App\Models\User::where('created_at', '>=', now()->subMonth())->count();
        @endphp

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Users -->
            <x-crm.card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-hando-gray-500 dark:text-hando-gray-400">Total Usuarios</p>
                        <p class="text-3xl font-bold text-hando-text-light dark:text-hando-text-dark mt-2">{{ $totalUsers }}</p>
                        <p class="text-xs text-hando-gray-500 dark:text-hando-gray-400 mt-2">
                            Usuarios registrados
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-hando flex items-center justify-center">
                        <svg class="w-6 h-6 text-hando-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                </div>
            </x-crm.card>

            <!-- Admin Users -->
            <x-crm.card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-hando-gray-500 dark:text-hando-gray-400">Administradores</p>
                        <p class="text-3xl font-bold text-hando-text-light dark:text-hando-text-dark mt-2">{{ $adminUsers }}</p>
                        <p class="text-xs text-hando-gray-500 dark:text-hando-gray-400 mt-2">
                            Con privilegios admin
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-hando flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                </div>
            </x-crm.card>

            <!-- Regular Users -->
            <x-crm.card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-hando-gray-500 dark:text-hando-gray-400">Usuarios Regulares</p>
                        <p class="text-3xl font-bold text-hando-text-light dark:text-hando-text-dark mt-2">{{ $regularUsers }}</p>
                        <p class="text-xs text-hando-gray-500 dark:text-hando-gray-400 mt-2">
                            Sin privilegios admin
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-hando flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                </div>
            </x-crm.card>

            <!-- Recent Users -->
            <x-crm.card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-hando-gray-500 dark:text-hando-gray-400">Nuevos Este Mes</p>
                        <p class="text-3xl font-bold text-hando-text-light dark:text-hando-text-dark mt-2">{{ $recentUsers }}</p>
                        <p class="text-xs text-hando-gray-500 dark:text-hando-gray-400 mt-2">
                            Últimos 30 días
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-hando flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                    </div>
                </div>
            </x-crm.card>
        </div>

        <!-- Recent Users & Quick Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Recent Users List -->
            <div class="lg:col-span-2">
                <x-crm.card title="Usuarios Recientes">
                    @php
                        $latestUsers = \App\Models\User::latest()->take(5)->get();
                    @endphp
                    <div class="space-y-4">
                        @forelse($latestUsers as $user)
                        <div class="flex items-start space-x-4 pb-4 border-b border-hando-border-light dark:border-hando-border-dark last:border-0 last:pb-0">
                            <div class="w-10 h-10 bg-gradient-to-br from-hando-primary to-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-white font-semibold text-sm">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-hando-text-light dark:text-hando-text-dark">{{ $user->name }}</p>
                                <p class="text-xs text-hando-gray-500 dark:text-hando-gray-400 mt-1">{{ $user->email }}</p>
                                <p class="text-xs text-hando-gray-500 dark:text-hando-gray-400 mt-1">
                                    Registrado {{ $user->created_at->diffForHumans() }}
                                </p>
                            </div>
                            @if($user->is_admin)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-600">
                                    Admin
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-hando-primary">
                                    Usuario
                                </span>
                            @endif
                        </div>
                        @empty
                        <div class="text-center py-8">
                            <p class="text-sm text-hando-gray-500 dark:text-hando-gray-400">No hay usuarios registrados</p>
                        </div>
                        @endforelse
                    </div>
                    @if($latestUsers->count() > 0)
                    <div class="mt-4 pt-4 border-t border-hando-border-light dark:border-hando-border-dark">
                        <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-hando-primary hover:text-hando-primary-hover transition-colors">
                            Ver todos los usuarios →
                        </a>
                    </div>
                    @endif
                </x-crm.card>
            </div>

            <!-- Quick Actions -->
            <div>
                <x-crm.card title="Acciones Rápidas">
                    <div class="space-y-3">
                        @if(Auth::user()->is_admin)
                        <a href="{{ route('admin.users.create') }}" class="flex items-center p-3 rounded-hando hover:bg-hando-gray-50 dark:hover:bg-hando-gray-700 transition-colors group">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-hando flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-hando-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-hando-text-light dark:text-hando-text-dark">Crear Usuario</p>
                                <p class="text-xs text-hando-gray-500 dark:text-hando-gray-400">Agregar nuevo usuario</p>
                            </div>
                        </a>

                        <a href="{{ route('admin.users.index') }}" class="flex items-center p-3 rounded-hando hover:bg-hando-gray-50 dark:hover:bg-hando-gray-700 transition-colors group">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-hando flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-hando-text-light dark:text-hando-text-dark">Gestionar Usuarios</p>
                                <p class="text-xs text-hando-gray-500 dark:text-hando-gray-400">Ver todos los usuarios</p>
                            </div>
                        </a>
                        @endif

                        <a href="{{ route('crm.profile.settings') }}" class="flex items-center p-3 rounded-hando hover:bg-hando-gray-50 dark:hover:bg-hando-gray-700 transition-colors group">
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-hando flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-hando-text-light dark:text-hando-text-dark">Mi Perfil</p>
                                <p class="text-xs text-hando-gray-500 dark:text-hando-gray-400">Editar mi perfil</p>
                            </div>
                        </a>
                    </div>
                </x-crm.card>

                @if(Auth::user()->is_admin)
                <!-- System Info -->
                <x-crm.card title="Información del Sistema" class="mt-6">
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-hando-gray-500 dark:text-hando-gray-400">Laravel</span>
                            <span class="font-medium text-hando-text-light dark:text-hando-text-dark">{{ app()->version() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-hando-gray-500 dark:text-hando-gray-400">PHP</span>
                            <span class="font-medium text-hando-text-light dark:text-hando-text-dark">{{ phpversion() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-hando-gray-500 dark:text-hando-gray-400">Entorno</span>
                            <span class="font-medium text-hando-text-light dark:text-hando-text-dark">{{ app()->environment() }}</span>
                        </div>
                    </div>
                </x-crm.card>
                @endif
            </div>
        </div>
    </div>
</x-layouts.crm>
