<x-layouts.crm>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-hando-text-light dark:text-hando-text-dark">Acceso no disponible</h1>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <x-crm.card>
            <div class="text-center py-8 space-y-4">
                <div class="w-14 h-14 mx-auto bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center">
                    <svg class="w-7 h-7 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>

                <h2 class="text-lg font-semibold text-hando-text-light dark:text-hando-text-dark">
                    La licencia de esta aplicación está inactiva
                </h2>

                <p class="text-sm text-hando-gray-500 dark:text-hando-gray-400 max-w-md mx-auto">
                    No podés operar en este momento. Por favor, contactá a tu administrador
                    para que regularice la licencia y restablezca el acceso.
                </p>
            </div>
        </x-crm.card>
    </div>
</x-layouts.crm>
