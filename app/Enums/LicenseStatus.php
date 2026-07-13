<?php

namespace App\Enums;

enum LicenseStatus: string
{
    /** No hay ninguna licencia activada. */
    case Unlicensed = 'unlicensed';

    /** Licencia vigente (dentro de valid_until). */
    case Valid = 'valid';

    /** Vencida pero dentro de la ventana de gracia: acceso con banner. */
    case Grace = 'grace';

    /** Bloqueada: gracia agotada, revocada, o reloj no confiable. */
    case Blocked = 'blocked';
}
