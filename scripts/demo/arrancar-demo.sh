#!/usr/bin/env bash
#
# Arranca todo lo necesario para la demo de licenciamiento y verifica que
# responde. Si algo no queda verde, lo dice y corta: mejor enterarse acá que
# frente a la sala.
#
#   ./scripts/demo/arrancar-demo.sh
#
set -uo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$RAIZ"

ok()    { printf '  \033[32m✓\033[0m %s\n' "$1"; }
falla() { printf '  \033[31m✗\033[0m %s\n' "$1"; FALLAS=$((FALLAS + 1)); }
paso()  { printf '\n\033[1m%s\033[0m\n' "$1"; }

FALLAS=0

paso "1. Motor de contenedores (Colima)"
if colima status >/dev/null 2>&1; then
    ok "Colima ya está corriendo"
else
    echo "  Colima está apagado, arrancándolo (puede tardar ~30s)..."
    colima start >/dev/null 2>&1 && ok "Colima arrancó" || falla "Colima no arrancó"
fi

paso "2. Base de datos (MySQL)"
docker start adm-mysql-local >/dev/null 2>&1
printf '  Esperando a que MySQL responda'
for _ in $(seq 1 30); do
    if docker exec adm-mysql-local mysqladmin ping -h localhost --silent >/dev/null 2>&1; then
        printf '\n'; ok "MySQL responde"; break
    fi
    printf '.'; sleep 2
done
docker exec adm-mysql-local mysqladmin ping -h localhost --silent >/dev/null 2>&1 \
    || { printf '\n'; falla "MySQL no responde"; }

paso "3. Aplicación (Laravel)"
if lsof -i :8000 -sTCP:LISTEN >/dev/null 2>&1; then
    ok "Ya hay algo escuchando en :8000"
else
    php artisan serve --port=8000 >/dev/null 2>&1 &
    sleep 3
    lsof -i :8000 -sTCP:LISTEN >/dev/null 2>&1 \
        && ok "Servidor levantado en :8000" \
        || falla "El servidor no levantó"
fi

paso "4. Verificación real (no asumimos: probamos)"
CODIGO=$(curl -s -o /dev/null -w '%{http_code}' --max-time 10 http://localhost:8000/login)
[ "$CODIGO" = "200" ] && ok "Login responde HTTP 200" || falla "Login respondió HTTP $CODIGO"

ESTADO=$(php artisan tinker --execute="
\$s = App\Models\LicenseState::first();
echo \$s ? app(App\Services\LicenseVerifier::class)->resolveStatus(\$s)->value : 'sin-licencia';
" 2>/dev/null | tail -1 | tr -d '[:space:]')

case "$ESTADO" in
    valid) ok "Licencia ACTIVA y válida" ;;
    sin-licencia) falla "No hay licencia activada — activala en /license/activation" ;;
    *) falla "Licencia en estado inesperado: $ESTADO" ;;
esac

paso "5. Servidor de licencias (opcional — no lo necesita la demo del cliente)"
CODIGO_WF=$(curl -s -o /dev/null -w '%{http_code}' --max-time 5 http://localhost:8080/ 2>/dev/null)
[ "$CODIGO_WF" = "200" ] \
    && ok "WildFly responde en :8080 (consola en :9990)" \
    || echo "  · WildFly no responde — no pasa nada, la demo del cliente no lo usa"

echo
if [ "$FALLAS" -eq 0 ]; then
    printf '\033[1;32m  TODO VERDE — la demo está lista.\033[0m\n\n'
    echo "  Abrí: http://localhost:8000"
    echo "  Guion: scripts/demo/GUION.md"
    echo
    exit 0
fi

printf '\033[1;31m  HAY %s PROBLEMA(S) — resolvelos ANTES de la reunión.\033[0m\n\n' "$FALLAS"
exit 1
