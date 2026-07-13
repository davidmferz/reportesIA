<?php

// Se pinean hasta dos claves para que la del licensing-server y la del helper de desarrollo
// conviyan: con una sola ranura, activar la del servidor invalidaría de golpe toda licencia
// ya emitida por el helper. El verificador elige por el `kid` del header, así que sobran.
$pinnedKeys = [];

foreach ([['LICENSE_KID', 'LICENSE_PUBLIC_KEY'], ['LICENSE_KID_2', 'LICENSE_PUBLIC_KEY_2']] as [$kidVar, $keyVar]) {
    $kid = env($kidVar);
    $key = env($keyVar);

    if ($kid && $key) {
        $pinnedKeys[$kid] = $key;
    }
}

return [

    /*
    |--------------------------------------------------------------------------
    | Servidor de licencias
    |--------------------------------------------------------------------------
    |
    | URL base del licensing-server (Change A) e identidad de este cliente. El
    | comando license:check hace phone-home contra
    | {server_url}/api/v1/licenses/{client_id}/validate.
    */

    'server_url' => env('LICENSE_SERVER_URL'),
    'client_id' => env('LICENSE_CLIENT_ID'),
    'product_id' => env('LICENSE_PRODUCT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Ventanas de revalidación y gracia
    |--------------------------------------------------------------------------
    |
    | revalidation_hours: cada cuánto se agenda license:check (default 24).
    | grace_days: días de operación permitidos tras valid_until cuando no se
    | puede revalidar contra el servidor (default 14).
    */

    'revalidation_hours' => (int) env('LICENSE_REVALIDATION_HOURS', 24),
    'grace_days' => (int) env('LICENSE_GRACE_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Claves públicas de verificación (JWS / EdDSA — Ed25519)
    |--------------------------------------------------------------------------
    |
    | Keyset local indexado por `kid`. El verificador selecciona la pública por
    | el header `kid` del token. Pineá al menos una clave localmente. La
    | rotación del servidor se resuelve vía JWKS (ver jwks_url) sin redeploy.
    */

    'keys' => $pinnedKeys,

    /*
    |--------------------------------------------------------------------------
    | JWKS (rotación de claves)
    |--------------------------------------------------------------------------
    |
    | Endpoint JWKS del servidor para el producto. Si es null, se puede derivar
    | de {server_url}/api/v1/products/{product_id}/jwks. Una respuesta JWKS de
    | red NUNCA sobreescribe una clave pineada localmente salvo opt-in explícito
    | con trust_network_jwks=true.
    */

    'jwks_url' => env('LICENSE_JWKS_URL'),
    'trust_network_jwks' => (bool) env('LICENSE_TRUST_NETWORK_JWKS', false),

    /*
    |--------------------------------------------------------------------------
    | Cache de la JWKS
    |--------------------------------------------------------------------------
    |
    | Horas que se cachea (Cache::store por defecto) la respuesta JWKS antes de
    | volver a pedirla al servidor.
    */

    'jwks_cache_hours' => (int) env('LICENSE_JWKS_CACHE_HOURS', 24),

];
