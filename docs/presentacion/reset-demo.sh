#!/usr/bin/env bash
# Resetea el entorno al PUNTO DE ARRANQUE de la demo de licenciamiento:
#   - Servidor de licencias: libera client_id=reportesia (borra licencia + hijos) para poder emitir.
#   - Cliente reportesIA: vacía license_states -> la app queda BLOQUEADA (Acto 1).
# Correr ANTES de cada ensayo. Requiere Colima + los contenedores arriba.
set -euo pipefail

echo "== Servidor: liberar client_id=reportesia =="
docker exec licensing-server-runtime-db-1 psql -U licensing -d licensing -c "
BEGIN;
DELETE FROM validation_event WHERE license_id IN (SELECT id FROM license WHERE client_id='reportesia');
DELETE FROM license_token   WHERE license_id IN (SELECT id FROM license WHERE client_id='reportesia');
DELETE FROM license          WHERE client_id='reportesia';
COMMIT;" 2>&1 | grep -v '^\s*$' || true
echo "   licencias reportesia restantes: $(docker exec licensing-server-runtime-db-1 psql -U licensing -d licensing -t -A -c "select count(*) from license where client_id='reportesia';")"

echo "== Cliente: vaciar license_states (app bloqueada) =="
docker exec adm-mysql-local mysql -uroot -proot -e "DELETE FROM reportesIA.license_states;" 2>/dev/null
echo "   filas license_states: $(docker exec adm-mysql-local mysql -uroot -proot -N -e "select count(*) from reportesIA.license_states;" 2>/dev/null)"

echo "LISTO: empezá la demo por el Acto 1 (app bloqueada)."
