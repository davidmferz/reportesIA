<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'ReportesIA') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased bg-gray-50 dark:bg-gray-900">
        <div class="min-h-screen flex flex-col items-center justify-center">
            <!-- Logo genérico -->
            <div class="mb-8">
                <div class="w-24 h-24 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg">
                    <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>

            <!-- Título -->
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                {{ config('app.name', 'ReportesIA') }}
            </h1>

            <p class="text-lg text-gray-600 dark:text-gray-400 mb-8">
                Sistema de Reportes Inteligentes
            </p>

            <!-- Botones de acción -->
            <div class="flex gap-4">
                @auth
                    <a href="{{ url('/dashboard') }}"
                       class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 transition duration-300">
                        Ir al Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 transition duration-300">
                        Iniciar Sesión
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                           class="px-6 py-3 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                            Registrarse
                        </a>
                    @endif
                @endauth
            </div>

            <!-- Footer -->
            <div class="absolute bottom-8 text-sm text-gray-500 dark:text-gray-400">
                <p>&copy; {{ date('Y') }} {{ config('app.name', 'ReportesIA') }}. Todos los derechos reservados.</p>
            </div>
        </div>
    </body>
</html>
