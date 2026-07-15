# Guion de demo — Sistema de Licenciamiento

**Objetivo:** mostrarle a tu jefe que el licenciamiento está TERMINADO y funcionando de punta a punta.
**Duración:** ~8-10 min. **Regla de oro:** contá el POR QUÉ de cada acto, no solo el qué.

---

## ⚠️ LOS 3 TROPIEZOS QUE ROMPEN LA DEMO (validados en ensayo — respetalos)

1. **Sesión del portal vencida (`ViewExpiredException`).** Si el portal queda abierto un rato, la
   sesión JSF caduca y el botón "Emitir" NO HACE NADA. → **Logueate en el portal JUSTO antes** de que
   entre tu jefe. Si el botón no responde, recargá con F5 y volvé a entrar.
2. **`client_id` en minúscula y exacto: `reportesia`.** Si lo dejás vacío o ponés `reportesIA`
   (mayúsculas), el cliente rechaza el token o el phone-home no encuentra la licencia.
3. **Dominio: `localhost`.** El cliente compara contra `APP_URL` (=`http://localhost`). Si emitís con
   `127.0.0.1` u otro dominio, el token es rechazado. Aunque abras el navegador en `127.0.0.1:8000`, en
   el portal va `localhost`.

**Una licencia por cliente:** el portal NO deja emitir una segunda licencia para el mismo `client_id`
(constraint única). Para volver a emitir en un ensayo, corré primero **`reset-demo.sh`** (deja la app
bloqueada y libera el `client_id`). En la demo real emitís una sola vez, así que no molesta.

---

## Antes de empezar (checklist de que todo está arriba)

| Pieza | URL | Debe dar |
|-------|-----|----------|
| Portal de licencias | http://localhost:8080/licensing-server/login.xhtml | Pantalla de login |
| Cliente reportesIA | http://127.0.0.1:8000/login | Pantalla de login |

**Credenciales**
- Portal: `admin` / `admin123`
- Cliente reportesIA: `admin@blmovil.com` (o `david.melchor@blmovil.com`)

**Las dos ventanas del navegador ya abiertas y logueadas** antes de que entre tu jefe. No lo hagas esperar en el login.

---

## La idea en una frase (para arrancar)

> "El software solo funciona si tiene una licencia válida y vigente. Nosotros, desde un portal aparte,
> emitimos esa licencia, la controlamos y la podemos cortar. La app valida sola, sin depender de que el
> servidor esté siempre prendido."

Esa última parte —**valida sola, offline**— es tu punto fuerte técnico. Repetila.

---

## ACTO 1 — La app SIN licencia está bloqueada

**Qué mostrás:** entrás a reportesIA y la app está bloqueada, redirige a la pantalla de activación.

**Qué decís:**
> "Miren: sin una licencia válida, la aplicación no deja trabajar. No es un cartel decorativo —
> el sistema entero está protegido a nivel de cada pantalla."

**Validación (yo confirmo):** que `license_states` del cliente esté vacío/vencido → estado `Bloqueado`.

---

## ACTO 2 — Emitir la licencia desde el portal

**Qué mostrás:** en el portal → **Licencias** → botón **"Emitir licencia"** → elegís producto
(reportesIA) y cliente (Cliente Demo), fecha de vigencia y cantidad de usuarios → **"Emitir"**.
Aparece un **token** en el cuadro de texto. Lo copiás.

**Qué decís:**
> "Acá, como consultora, emitimos la licencia para este cliente. Le ponemos hasta cuándo vale y para
> cuántos usuarios. El sistema genera este token firmado digitalmente — es imposible de falsificar."

**Validación (yo confirmo):** aparece una fila nueva en la tabla `license` del servidor con el
`valid_until` y `max_users` que pusiste.

> ⚠️ **Detalle cosmético:** al emitir, el modal NO se cierra solo (solo se limpia el formulario). No es
> que falló — el token aparece en el **panel verde** detrás del modal. Cerrá el modal con la ✕ y copiá
> el token desde el panel verde con "Copiar al portapapeles".

---

## ACTO 3 — Activar en el cliente → la app se desbloquea

**Qué mostrás:** volvés a reportesIA, pantalla de activación, **pegás el token**, **Activar**.
La app se desbloquea y entrás al dashboard normalmente.

**Qué decís:**
> "El cliente pega el token y listo: la aplicación se desbloquea. Y fíjense —la app verificó la firma
> ELLA SOLA, sin llamar a ningún servidor. Funciona aunque no haya internet. El servidor no es un punto
> único de falla."

**Validación (yo confirmo):** fila nueva en `license_states` del cliente con `last_check_result` y
`activated_at`; estado pasa a `Válido`.

---

## ACTO 4 — El período de gracia (no te cortamos de golpe)

**Qué mostrás:** con una licencia recién vencida, la app sigue funcionando pero muestra un **banner de
aviso** ("tu licencia venció, te quedan X días").

**Qué decís:**
> "Cuando una licencia vence, no dejamos al cliente tirado de un día para el otro. Hay un período de
> gracia configurable —hoy 14 días— donde la app sigue andando pero avisa. Es respeto por el cliente
> y a la vez presión comercial para que renueve."

**Validación (yo confirmo):** seteo `valid_until` a ayer → recargás → aparece el banner de gracia.

---

## ACTO 5 — Vencido/revocado → bloqueo, con mensaje humano

**Qué mostrás:** con la gracia agotada (o licencia revocada), la app se bloquea de nuevo. Si el usuario
NO es admin, ve un mensaje **"contactá a tu administrador"** —no un error feo 403.

**Qué decís:**
> "Pasada la gracia, se bloquea. Y miren el detalle: si el que entra no es administrador, no ve un error
> técnico, ve un mensaje claro de a quién contactar. Está pensado para el usuario final."

**Validación (yo confirmo):** seteo `valid_until` + gracia en el pasado → estado `Bloqueado`;
con usuario no-admin → pantalla de contacto.

---

## Cierre (30 segundos)

> "Resumiendo: emitimos licencias desde un portal central, la app las valida sola y offline, respeta un
> período de gracia, y podemos cortar el acceso cuando corresponde. El sistema está terminado y probado
> —118 pruebas automáticas en verde—. Está listo para usarse."

---

## Herramientas para manejar la demo vos solo (en docs/presentacion/)

- **`reset-demo.sh`** → deja la app BLOQUEADA y libera el `client_id`. Corrélo ANTES de cada ensayo o
  antes de la demo real, para arrancar limpio desde el Acto 1.
- **`estado-demo.sh valid|grace|blocked`** → fuerza el estado de la licencia del cliente.
  La gracia y el bloqueo dependen del TIEMPO (no hay botón): este script "adelanta el reloj" cambiando
  la fecha de vencimiento en la base del cliente. Es la forma de mostrar los Actos 4 y 5 sin esperar días.
  - `./estado-demo.sh grace` → banner de gracia (Acto 4)
  - `./estado-demo.sh blocked` → app bloqueada (Acto 5, parte A)

**Levantar el entorno** (si Colima está apagado):
1. `colima start`
2. `docker compose -f ~/licensing-server/docker-compose.yml up -d` (portal)
3. `php artisan serve` (cliente reportesIA)

---

## Si algo se rompe en vivo (paracaídas)

- Si el portal no abre → lo levantamos en 1 min con `docker compose up -d` (imágenes ya construidas).
- Si el cliente no abre → `php artisan serve`.
- Tené a mano las **capturas del ensayo** de hoy. Si la máquina falla, mostrás las capturas y seguís hablando.

## Lo que hay que decir con honestidad (si preguntan)

- **La revocación no es instantánea:** viaja en la revalidación diaria (`license:check`), no al segundo
  de apretar el botón. Es a propósito: así la app funciona offline. Decilo vos antes de que lo pregunten.
- **Hoy corre en la máquina local.** Para producción falta hosting del servidor, HTTPS y respaldo de la
  clave privada. Es despliegue, no desarrollo — el software está terminado.
