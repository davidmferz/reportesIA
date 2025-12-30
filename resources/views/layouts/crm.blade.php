<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" :class="{ 'dark': darkMode }">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }} - CRM</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-hando-bg-light dark:bg-hando-bg-dark transition-colors duration-200">
            <!-- Sidebar -->
            <x-crm.sidebar />

            <!-- Main Content Area -->
            <div class="ml-64">
                <!-- Navbar -->
                <x-crm.navbar />

                <!-- Page Content -->
                <main class="pt-16 min-h-screen">
                    <!-- Page Header -->
                    @isset($header)
                        <div class="bg-white dark:bg-hando-card-dark border-b border-hando-border-light dark:border-hando-border-dark transition-colors duration-200">
                            <div class="px-6 py-6">
                                {{ $header }}
                            </div>
                        </div>
                    @endisset

                    <!-- Main Content -->
                    <div class="p-6">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

        <!-- Alpine.js Collapse Plugin (for sidebar menus) -->
        <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    </body>
</html>
