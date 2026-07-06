#!/usr/bin/env bash
set -euo pipefail

# Publish this Git workspace into a separate SVN working copy.
# Usage:
#   scripts/publish-svn.sh "Mensaje de commit"
# Optional env vars:
#   SVN_URL, SVN_WC, SVN_USER

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SRC_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

SVN_URL="${SVN_URL:-http://www.hova-it.net/repo/Clientes/IA_Consultoria/trunk/src/main/}"
SVN_WC="${SVN_WC:-/Users/desarrollo_app2/Documents/svn-work/ia_consultoria_main_wc}"
SVN_USER="${SVN_USER:-blmovil}"
COMMIT_MSG="${1:-Sync desde Git reportesIA $(date +%Y-%m-%d_%H-%M-%S)}"

if ! command -v svn >/dev/null 2>&1; then
  echo "Error: svn no esta instalado."
  exit 1
fi

if ! command -v rsync >/dev/null 2>&1; then
  echo "Error: rsync no esta instalado."
  exit 1
fi

mkdir -p "$(dirname "${SVN_WC}")"

if [[ -d "${SVN_WC}/.svn" ]]; then
  echo "SVN WC encontrada, ejecutando update..."
  svn update "${SVN_WC}"
else
  echo "Creando SVN WC con checkout inicial..."
  svn checkout --username "${SVN_USER}" "${SVN_URL}" "${SVN_WC}"
fi

echo "Sincronizando archivos de ${SRC_DIR} a ${SVN_WC}..."
rsync -av --delete \
  --filter='P .svn/' \
  --exclude '.git' \
  --exclude '.github' \
  --exclude '.DS_Store' \
  --exclude '.env' \
  --exclude 'node_modules' \
  --exclude 'vendor' \
  --exclude 'vendor_broken_*' \
  --exclude 'storage/logs' \
  --exclude 'storage/framework/cache/*' \
  --exclude 'storage/framework/sessions/*' \
  --exclude 'storage/framework/testing/*' \
  --exclude 'storage/framework/views/*' \
  --exclude 'storage/app/private/*' \
  --exclude 'database/database.sqlite' \
  "${SRC_DIR}/" "${SVN_WC}/"

cd "${SVN_WC}"

echo "Registrando nuevos archivos..."
svn add --force . --auto-props --parents --depth infinity -q

echo "Marcando borrados..."
svn status | rg '^!' -N | while IFS= read -r line; do
  missing_path="${line:8}"
  svn rm --force "${missing_path}" >/dev/null
  echo "D ${missing_path}"
done

change_count="$(svn status | wc -l | tr -d ' ')"
if [[ "${change_count}" == "0" ]]; then
  echo "No hay cambios para commit en SVN."
  exit 0
fi

echo "Resumen de cambios:"
svn status

echo "Enviando commit a SVN..."
svn commit -m "${COMMIT_MSG}"

echo "Publicacion a SVN completada."
