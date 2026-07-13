@isset($licenseGraceUntil)
    <div class="bg-orange-100 dark:bg-orange-900/30 border-b border-orange-200 dark:border-orange-800 px-4 py-3 text-sm text-orange-800 dark:text-orange-300 text-center">
        <strong class="font-semibold">Licencia en período de gracia:</strong>
        vence el {{ $licenseGraceUntil->format('d/m/Y') }}. Contactá a tu administrador para regularizarla antes de esa fecha.
    </div>
@endisset
