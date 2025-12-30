<nav class="h-16 bg-white dark:bg-hando-card-dark border-b border-hando-border-light dark:border-hando-border-dark fixed top-0 right-0 left-64 z-10 transition-colors duration-200">
    <div class="h-full px-6 flex items-center justify-between">
        <!-- Search Bar -->
        <div class="flex-1 max-w-xl">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-hando-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input
                    type="text"
                    placeholder="Search..."
                    class="block w-full pl-10 pr-3 py-2 border border-hando-border-light dark:border-hando-border-dark rounded-hando bg-hando-gray-50 dark:bg-hando-gray-800 text-hando-text-light dark:text-hando-text-dark placeholder-hando-gray-400 focus:outline-none focus:ring-2 focus:ring-hando-primary focus:border-transparent transition-colors duration-200 text-sm"
                />
            </div>
        </div>

        <!-- Right Side: Dark Mode Toggle, Notifications, Profile -->
        <div class="flex items-center space-x-4">
            <!-- Dark Mode Toggle -->
            <button
                @click="darkMode = !darkMode"
                class="p-2 rounded-hando text-hando-gray-600 dark:text-hando-gray-400 hover:bg-hando-gray-100 dark:hover:bg-hando-gray-700 transition-colors duration-150"
                aria-label="Toggle dark mode"
            >
                <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
                <svg x-show="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </button>

            <!-- Notifications -->
            <div class="relative" x-data="{ open: false }">
                <button
                    @click="open = !open"
                    class="relative p-2 rounded-hando text-hando-gray-600 dark:text-hando-gray-400 hover:bg-hando-gray-100 dark:hover:bg-hando-gray-700 transition-colors duration-150"
                    aria-label="Notifications"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <!-- Badge -->
                    <span class="absolute top-1 right-1 block h-2 w-2 rounded-full bg-hando-danger ring-2 ring-white dark:ring-hando-card-dark"></span>
                </button>

                <!-- Dropdown -->
                <div
                    x-show="open"
                    @click.away="open = false"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute right-0 mt-2 w-80 rounded-hando bg-white dark:bg-hando-card-dark shadow-hando-md border border-hando-border-light dark:border-hando-border-dark overflow-hidden"
                    style="display: none;"
                >
                    <div class="px-4 py-3 border-b border-hando-border-light dark:border-hando-border-dark">
                        <p class="text-sm font-semibold text-hando-text-light dark:text-hando-text-dark">Notifications</p>
                    </div>
                    <div class="max-h-64 overflow-y-auto">
                        <a href="#" class="flex items-start px-4 py-3 hover:bg-hando-gray-50 dark:hover:bg-hando-gray-700 transition-colors">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-hando-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-hando-text-light dark:text-hando-text-dark">New user registered</p>
                                <p class="text-xs text-hando-gray-500 dark:text-hando-gray-400 mt-1">2 minutes ago</p>
                            </div>
                        </a>
                        <a href="#" class="flex items-start px-4 py-3 hover:bg-hando-gray-50 dark:hover:bg-hando-gray-700 transition-colors">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-hando-text-light dark:text-hando-text-dark">Task completed</p>
                                <p class="text-xs text-hando-gray-500 dark:text-hando-gray-400 mt-1">1 hour ago</p>
                            </div>
                        </a>
                    </div>
                    <div class="px-4 py-3 border-t border-hando-border-light dark:border-hando-border-dark">
                        <a href="#" class="text-sm font-medium text-hando-primary hover:text-hando-primary-hover">View all notifications</a>
                    </div>
                </div>
            </div>

            <!-- Profile Dropdown -->
            <div class="relative" x-data="{ open: false }">
                <button
                    @click="open = !open"
                    class="flex items-center space-x-3 focus:outline-none"
                >
                    <div class="flex items-center space-x-3 px-3 py-2 rounded-hando hover:bg-hando-gray-100 dark:hover:bg-hando-gray-700 transition-colors duration-150">
                        <img
                            src="https://ui-avatars.com/api/?name={{ Auth::user()->name ?? 'User' }}&background=3B82F6&color=fff"
                            alt="Profile"
                            class="w-8 h-8 rounded-full"
                        />
                        <div class="text-left">
                            <p class="text-sm font-medium text-hando-text-light dark:text-hando-text-dark">{{ Auth::user()->name ?? 'User' }}</p>
                            <p class="text-xs text-hando-gray-500 dark:text-hando-gray-400">{{ Auth::user()->email ?? 'user@example.com' }}</p>
                        </div>
                        <svg class="w-4 h-4 text-hando-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </button>

                <!-- Dropdown Menu -->
                <div
                    x-show="open"
                    @click.away="open = false"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute right-0 mt-2 w-48 rounded-hando bg-white dark:bg-hando-card-dark shadow-hando-md border border-hando-border-light dark:border-hando-border-dark overflow-hidden"
                    style="display: none;"
                >
                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-hando-text-light dark:text-hando-text-dark hover:bg-hando-gray-100 dark:hover:bg-hando-gray-700">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Profile
                        </div>
                    </a>
                    <a href="#" class="block px-4 py-2 text-sm text-hando-text-light dark:text-hando-text-dark hover:bg-hando-gray-100 dark:hover:bg-hando-gray-700">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Settings
                        </div>
                    </a>
                    <hr class="border-hando-border-light dark:border-hando-border-dark">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-hando-danger hover:bg-red-50 dark:hover:bg-red-900/20">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                Logout
                            </div>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</nav>
