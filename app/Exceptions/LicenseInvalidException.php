<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Se lanza cuando un token de licencia no supera la verificación criptográfica
 * o estructural (firma, algoritmo, kid, claims requeridos, dominio).
 */
class LicenseInvalidException extends RuntimeException
{
}
