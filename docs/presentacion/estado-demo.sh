#!/usr/bin/env bash
# Fuerza el ESTADO de la licencia del cliente para la demo (Actos 4 y 5).
# Grace y bloqueo dependen del TIEMPO: no hay botón, se "adelanta el reloj"
# cambiando valid_until en la base del cliente. Esto es lo que hace este script.
#
# Uso:
#   ./estado-demo.sh valid     -> licencia vigente (app funciona normal)
#   ./estado-demo.sh grace     -> vencida ayer (banner de gracia, sigue funcionando)
#   ./estado-demo.sh blocked   -> vencida hace 30 días (app bloqueada)
#
# Requiere que ya exista una licencia activada en el cliente (Actos 2-3 hechos).
set -euo pipefail
ESTADO="${1:-}"

case "$ESTADO" in
  valid)   SQL="valid_until = DATE_ADD(NOW(), INTERVAL 1 YEAR)";  MSG="VIGENTE (app normal)";;
  grace)   SQL="valid_until = DATE_SUB(NOW(), INTERVAL 1 DAY)";   MSG="GRACIA (banner de aviso)";;
  blocked) SQL="valid_until = DATE_SUB(NOW(), INTERVAL 30 DAY)";  MSG="BLOQUEADA";;
  *) echo "Uso: $0 valid|grace|blocked"; exit 1;;
esac

docker exec adm-mysql-local mysql -uroot -proot -e "UPDATE reportesIA.license_states SET $SQL;" 2>/dev/null
echo "Estado forzado -> $MSG"
echo "Ahora recargá http://127.0.0.1:8000 en el navegador para verlo."
