<?php

/**
 * Genera un par de claves Ed25519 local + un JWS/EdDSA de licencia firmado,
 * para activar y testear el cliente OFFLINE, sin el licensing-server corriendo.
 * Espeja lo que emitirá el servidor (Change A): JWS compacto, alg EdDSA, kid.
 *
 * La clave privada NUNCA se commitea (vive en storage/license-dev, gitignoreado).
 *
 * Uso:
 *   php scripts/license/make-dev-token.php [domain] [days] [maxUsers] [clientId]
 *
 * Sin argumentos: domain = host de APP_URL, 365 días, 25 usuarios, client_id
 * = LICENSE_CLIENT_ID (o "reportesia").
 */

require __DIR__ . '/../../vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$b64url = static fn (string $bin): string => rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');

// --- Parámetros ---------------------------------------------------------------
$appHost   = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
$domain    = $argv[1] ?? $appHost;
$days      = (int) ($argv[2] ?? 365);
$maxUsers  = (int) ($argv[3] ?? 25);
$clientId  = $argv[4] ?? (config('license.client_id') ?: 'reportesia');
$kid       = 'dev-k1';

// --- Keypair persistido (se reusa entre corridas) -----------------------------
$dir        = __DIR__ . '/../../storage/license-dev';
$secretFile = $dir . '/ed25519.secret';
$publicFile = $dir . '/ed25519.public';

if (is_file($secretFile) && is_file($publicFile)) {
    $secret = trim((string) file_get_contents($secretFile));
    $public = trim((string) file_get_contents($publicFile));
} else {
    if (! is_dir($dir)) {
        mkdir($dir, 0700, true);
    }
    $pair   = sodium_crypto_sign_keypair();
    $secret = $b64url(sodium_crypto_sign_secretkey($pair));
    $public = $b64url(sodium_crypto_sign_publickey($pair));
    file_put_contents($secretFile, $secret);
    file_put_contents($publicFile, $public);
    chmod($secretFile, 0600);
}

// --- Claims (los 8 requeridos por el verificador) -----------------------------
$now    = new DateTimeImmutable();
$claims = [
    'client_id'      => $clientId,
    'domain'         => $domain,
    'valid_from'     => $now->format(DATE_ATOM),
    'valid_until'    => $now->add(new DateInterval("P{$days}D"))->format(DATE_ATOM),
    'max_users'      => $maxUsers,
    'issued_at'      => $now->format(DATE_ATOM),
    'schema_version' => 1,
    'kid'            => $kid,
];

$token = \Firebase\JWT\JWT::encode($claims, $secret, 'EdDSA', $kid);

// --- Salida -------------------------------------------------------------------
$line = str_repeat('─', 72);

echo "\n{$line}\n";
echo "  TOKEN DE LICENCIA DEV (JWS / EdDSA)\n";
echo "{$line}\n";
echo "  dominio      : {$domain}\n";
echo "  client_id    : {$clientId}\n";
echo "  max_users    : {$maxUsers}\n";
echo "  válida hasta : {$claims['valid_until']}\n";
echo "  kid          : {$kid}\n";
echo "{$line}\n\n";

echo "1) Pegá estas dos líneas en tu .env y corré `php artisan config:clear`:\n\n";
echo "   LICENSE_KID={$kid}\n";
echo "   LICENSE_PUBLIC_KEY={$public}\n\n";

echo "2) Iniciá sesión como admin, entrá a /license/activation y pegá este token:\n\n";
echo "{$token}\n\n";

echo "Nota: el dominio del token debe coincidir con el host de APP_URL ({$appHost}).\n";
echo "La clave privada quedó en storage/license-dev (NO se commitea).\n\n";
