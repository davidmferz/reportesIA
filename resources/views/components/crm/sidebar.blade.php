@props(['currentRoute' => ''])

<aside
    class="fixed left-0 top-0 h-screen w-64 bg-white dark:bg-hando-card-dark border-r border-hando-border-light dark:border-hando-border-dark transition-colors duration-200 overflow-y-auto"
    x-data="{ openMenu: null }"
>
    <!-- Logo -->
    <div class="h-16 flex items-center px-6 border-b border-hando-border-light dark:border-hando-border-dark">
        <a href="{{ route('dashboard') }}" class="flex items-center space-x-3">
            
            <span class="text-xl font-bold text-hando-text-light dark:text-hando-text-dark">Reportes IA</span>
        </a>
    </div>

    <!-- Navigation -->
    <nav class="p-4 space-y-6">
        <!-- MENU Section -->
        <div>
            <p class="px-3 mb-2 text-xs font-semibold text-hando-gray-500 dark:text-hando-gray-400 uppercase tracking-wider">
                Menu
            </p>
            <ul class="space-y-1">
                <li>
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center px-3 py-2.5 rounded-hando text-sm font-medium transition-colors duration-150
                              {{ request()->routeIs('dashboard') ? 'bg-blue-50 dark:bg-blue-900/20 text-hando-primary border-l-4 border-hando-primary' : 'text-hando-gray-600 dark:text-hando-gray-400 hover:bg-hando-gray-100 dark:hover:bg-hando-gray-700' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Dashboard
                    </a>
                </li>
            </ul>
        </div>

        <!-- ADMIN Section (solo visible para administradores) -->
        @if(Auth::user()->is_admin)
        <div>
            <p class="px-3 mb-2 text-xs font-semibold text-hando-gray-500 dark:text-hando-gray-400 uppercase tracking-wider">
                Admin
            </p>
            <ul class="space-y-1">
                <li>
                    <a href="{{ route('admin.users.index') }}"
                       class="flex items-center px-3 py-2.5 rounded-hando text-sm font-medium transition-colors duration-150
                              {{ request()->routeIs('admin.users.*') ? 'bg-blue-50 dark:bg-blue-900/20 text-hando-primary border-l-4 border-hando-primary' : 'text-hando-gray-600 dark:text-hando-gray-400 hover:bg-hando-gray-100 dark:hover:bg-hando-gray-700' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        Users
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.report-types.index') }}"
                       class="flex items-center px-3 py-2.5 rounded-hando text-sm font-medium transition-colors duration-150
                              {{ request()->routeIs('admin.report-types.*') ? 'bg-blue-50 dark:bg-blue-900/20 text-hando-primary border-l-4 border-hando-primary' : 'text-hando-gray-600 dark:text-hando-gray-400 hover:bg-hando-gray-100 dark:hover:bg-hando-gray-700' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Tipos de Reportes
                    </a>
                </li>
            </ul>
        </div>
        @endif
    </nav>
</aside>
