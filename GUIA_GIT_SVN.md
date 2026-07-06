# Guia Git + SVN para reportesIA

## Objetivo
Mantener flujo de desarrollo en Git y, cuando sea necesario, publicar los cambios al repositorio SVN de Polarion.

## Contexto validado
- Proyecto local Git: `/Users/desarrollo_app2/Documents/reportesIA`
- URL SVN usada: `http://www.hova-it.net/repo/Clientes/IA_Consultoria/trunk/src/main/`
- Usuario SVN: `blmovil`
- Working copy SVN recomendada: `/Users/desarrollo_app2/Documents/svn-work/ia_consultoria_main_wc`

## Flujo recomendado (opcion B)
1. Trabajar normalmente en Git.
2. Publicar a SVN usando una working copy separada.
3. No mezclar metadatos de Git y SVN en la misma carpeta.

## Script automatico creado
Se creo el script:
- `scripts/publish-svn.sh`

Este script hace:
1. `svn checkout` inicial o `svn update`.
2. `rsync` desde Git hacia la working copy SVN preservando `.svn`.
3. `svn add` para archivos nuevos.
4. `svn rm` para borrados.
5. `svn commit` con el mensaje indicado.

## Uso rapido
Desde la raiz del proyecto:

```bash
./scripts/publish-svn.sh "Tu mensaje de commit SVN"
```

Si no envias mensaje, el script genera uno automaticamente con fecha/hora.

## Variables opcionales (sin editar el script)
```bash
SVN_URL="http://www.hova-it.net/repo/Clientes/IA_Consultoria/trunk/src/main/" \
SVN_USER="blmovil" \
SVN_WC="/Users/desarrollo_app2/Documents/svn-work/ia_consultoria_main_wc" \
./scripts/publish-svn.sh "Commit manual"
```

## Exclusiones importantes incluidas
Para evitar subir archivos sensibles o temporales, el script excluye entre otros:
- `.git`, `.github`, `.DS_Store`
- `.env`
- `node_modules`, `vendor`, `vendor_broken_*`
- `storage/logs`
- `storage/framework/cache/*`
- `storage/framework/sessions/*`
- `storage/framework/testing/*`
- `storage/framework/views/*`
- `storage/app/private/*`
- `database/database.sqlite`

## Verificacion manual (opcional)
Antes de commit, puedes revisar estado SVN:

```bash
cd /Users/desarrollo_app2/Documents/svn-work/ia_consultoria_main_wc
svn status
```

## Errores comunes y que significan
- `Password for 'usuario'`: pide la clave de SVN de ese usuario.
- `HTTP 500 Internal Server Error`: problema del servidor/ruta SVN (no de tu codigo local).
- `certificate has expired`: certificado TLS vencido en servidor HTTPS.
- `Authentication failed`: usuario o password incorrectos, o falta de permisos.

## Nota operativa
Ya se logro publicar correctamente una vez en SVN con revision `21167`, por lo que el flujo quedo funcional con el usuario `blmovil`.
