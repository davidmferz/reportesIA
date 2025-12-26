<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-2xl leading-tight tracking-tight" style="color: #26221F;">
                Dashboard
            </h2>
            <p class="mt-1 text-sm" style="color: #A89F94;">
                Bienvenido a tu panel de control
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl shadow-xl overflow-hidden" style="background-color: #FFFFFF; border: 1px solid #A89F94;">
                <div class="p-8">
                    <div class="flex items-center justify-center py-12">
                        <div class="text-center">
                            <div class="flex justify-center mb-6">
                                <div class="flex items-center justify-center w-20 h-20 rounded-2xl shadow-lg" style="background-color: #26221F;">
                                    <svg class="w-10 h-10" style="color: #FFFFFF;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="text-2xl font-bold mb-2" style="color: #26221F;">
                                ¡Has iniciado sesión correctamente!
                            </h3>
                            <p class="font-medium" style="color: #A89F94;">
                                Bienvenido de vuelta, {{ Auth::user()->name }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
