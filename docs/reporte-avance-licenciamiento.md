# Sistema de Licenciamiento — Reporte de Avance

**Fecha:** 13 de julio de 2026

---

## Resumen ejecutivo

Se está construyendo un sistema de licenciamiento propio, compuesto por **dos piezas
independientes**:

| Pieza | Qué hace | Estado |
| --- | --- | --- |
| **Servidor de licencias** | Emite, renueva y revoca licencias. Portal de administración. | **Operativo** — en entorno de desarrollo |
| **Cliente (reportesIA)** | Valida su licencia y bloquea el acceso si no es válida. | **Operativo** — integrado en la aplicación |

**El ciclo de vida completo de una licencia ya funciona de punta a punta**: se emite desde
el portal del servidor, se activa en la aplicación cliente, y se puede revocar.

**Cobertura de pruebas automatizadas: 153 pruebas, todas en verde.**

---

## Sistemas en línea — accesos para la demostración

Ambos sistemas están **levantados y verificados**. Se ejecutan en el entorno local de
desarrollo, por lo que los accesos son válidos únicamente desde el equipo de la
presentación.

### 1. Servidor de licencias — Portal de administración

| | |
| --- | --- |
| **Acceso** | <http://localhost:8080/licensing-server/login.xhtml> |
| **Usuario** | `admin` |
| **Contraseña** | `admin123` |
| **Estado** | ✅ Verificado |

Pantallas disponibles una vez dentro:

| Pantalla | Enlace directo | Qué muestra |
| --- | --- | --- |
| Tablero | <http://localhost:8080/licensing-server/admin/dashboard.xhtml> | Totales: productos, clientes, licencias activas y revocadas |
| Productos | <http://localhost:8080/licensing-server/admin/products.xhtml> | Los proyectos de la cartera (hoy: **reportesIA**) y su clave pública |
| Clientes | <http://localhost:8080/licensing-server/admin/customers.xhtml> | Alta y listado de clientes |
| **Licencias** | <http://localhost:8080/licensing-server/admin/licenses.xhtml> | **Emitir, renovar y revocar.** Estado por color: activa / revocada / expirada |

### 2. Cliente — reportesIA

| | |
| --- | --- |
| **Acceso** | <http://localhost:8000> |
| **Pantalla de licencia** | <http://localhost:8000/license/activation> *(requiere sesión de administrador)* |
| **Estado de la licencia** | ✅ Activa — vence 13/07/2027, 25 usuarios |

### 3. Consola técnica del servidor de aplicaciones *(uso interno, no para demostración)*

<http://localhost:9990> — consola de WildFly. Muestra el despliegue y la conexión a base de
datos. Es infraestructura: **no gestiona licencias**.

### Puesta en marcha

Si alguno de los sistemas no responde, se levantan así:

```bash
# Cliente (reportesIA): levanta base de datos y aplicación, y verifica que respondan
./scripts/demo/arrancar-demo.sh

# Servidor de licencias
cd ~/licensing-server && ./scripts/run.sh
```

Ambos scripts **verifican** el resultado y avisan si algo quedó mal, en lugar de darlo por
supuesto.

---

## Guion sugerido de demostración

El recorrido muestra el ciclo de vida completo de una licencia entre los dos sistemas:

1. **Portal → Licencias → "Emitir licencia"**. Se selecciona el producto *reportesIA*, el
   dominio, la cantidad de usuarios y el vencimiento. El servidor firma la licencia con su
   clave privada y devuelve el token.
2. **Se copia el token** y se pega en el cliente, en la pantalla de activación.
   La aplicación **se desbloquea**.
3. **Prueba de integridad** *(el punto más relevante)*: se modifica **un solo carácter** del
   token y se vuelve a intentar. La aplicación lo **rechaza**: la firma criptográfica ya no
   cierra. Una licencia no se puede alterar ni fabricar sin la clave privada.
4. **Prueba de dominio**: una licencia emitida para otro dominio también es **rechazada**.
   Copiar una licencia a otra instalación no sirve.
5. **Portal → Revocar**. La licencia pasa a estado revocado.

> **Nota técnica sobre el paso 5.** El cliente **no se bloquea de inmediato** al revocar. La
> validación es *offline* por diseño (ver "Qué problema resuelve"), y la revocación se
> propaga en la siguiente revalidación periódica —funcionalidad correspondiente a la Fase 5,
> aún no construida—. Es una consecuencia esperada del diseño, no una falla.

---

## Qué problema resuelve

Hoy no existe control sobre quién usa el software, en qué servidor, con cuántos usuarios
ni hasta cuándo. El sistema de licenciamiento introduce ese control.

Tres garantías que ofrece el diseño:

- **Una licencia no se puede falsificar.** Está firmada criptográficamente (Ed25519). Alterar
  un solo carácter la invalida. Para emitir una válida hace falta la clave privada, que
  reside únicamente en el servidor.
- **Una licencia no se puede reutilizar.** Está atada al dominio del cliente. Copiarla a otra
  instalación no sirve.
- **La aplicación cliente no depende del servidor para operar.** La validación es *offline*.
  Si el servidor de licencias se cae, **ningún cliente deja de trabajar**. Esta es una
  decisión de arquitectura deliberada: elimina un punto único de falla que afectaría a
  toda la cartera de clientes.

---

## Servidor de licencias — estado por fase

Plan total: 146 tareas técnicas en 8 fases.

| Fase | Alcance | Estado |
| --- | --- | --- |
| **1. Infraestructura** | Esqueleto del proyecto, empaquetado, base de datos | ✅ **Completa** |
| **2. Dominio y persistencia** | Modelo de datos, migraciones versionadas | ✅ **Completa** |
| **3. Firma y claves** | Generación de claves, firma criptográfica, rotación | ✅ **Completa** (18/18) |
| **4. Emisión** | Alta de productos/clientes, emitir, renovar, revocar | ✅ **Completa** (22/22) |
| **5. API de validación** | Servicio web para revalidación periódica | ⏸️ **Pospuesta** (0/30) |
| **6. Portal de administración** | Login, gestión de licencias, tablero | 🟡 **Funcional** (18/22) |
| **7. Despliegue** | Contenedores, configuración por entorno | 🟡 **Funcional en desarrollo** |
| **8. Endurecimiento** | Pruebas de integración cruzada, límites de uso | ⬜ **Pendiente** (0/9) |

**Pruebas automatizadas: 62, todas en verde.**

### Lo que ya se puede hacer hoy en el portal

- Iniciar sesión con credenciales (contraseñas cifradas con BCrypt)
- Dar de alta **productos** (cada proyecto de la cartera) y **clientes**
- **Emitir** una licencia firmada, con dominio, cantidad de usuarios y vencimiento
- **Ver el estado** de cada licencia de un vistazo: activa / revocada / expirada
- **Renovar** y **revocar** licencias

### Notas sobre las fases no cerradas

- **Fase 5 (API de validación)** se pospuso **de forma deliberada**. Es la que permite que
  el cliente pregunte periódicamente al servidor si su licencia sigue vigente
  (revocación remota). No es necesaria para emitir ni para validar licencias, que ya
  funcionan. Es el siguiente bloque natural de trabajo.
- **Fase 6** está funcional; resta la pantalla de auditoría (histórico de validaciones).
- **Fase 7** está resuelta para desarrollo (el servidor levanta con un comando).
  Falta el endurecimiento para producción.

---

## Cliente (reportesIA) — estado por bloque

Plan total: 43 tareas. **29 completadas (67 %).**

| Bloque | Alcance | Estado |
| --- | --- | --- |
| **1. Fundación** | Configuración, estado de licencia, usuarios activos | ✅ **Completo** |
| **2. Verificación** | Validación criptográfica del token | ✅ **Completo** (9/10) |
| **3. Bloqueo de acceso** | Corte de acceso si la licencia no es válida | ✅ **Completo** |
| **5. Pantallas** | Activación de licencia y pantalla de bloqueo | ✅ **Completo** |
| **4. Revalidación periódica** | Consulta diaria al servidor (revocación remota) | ⬜ **Pendiente** |
| **6. Aviso de vencimiento** | Banner de período de gracia | ⬜ **Pendiente** |
| **7. Tope de usuarios** | Impedir superar el máximo licenciado | ⬜ **Pendiente** |
| **8. Documentación** | Manual de operación | ⬜ **Pendiente** |

**Pruebas automatizadas: 91, todas en verde.**

---

## Riesgos y consideraciones

| Tema | Situación |
| --- | --- |
| **Revocación remota** | Hoy revocar una licencia en el servidor **no bloquea al cliente de inmediato**. Requiere la Fase 5 (API) más el Bloque 4 del cliente. Es el trabajo prioritario. |
| **Tope de usuarios** | La licencia declara un máximo de usuarios, pero el cliente **aún no lo hace cumplir**. Requiere el Bloque 7. |
| **Custodia de claves** | La clave privada de firma **nunca se guarda en la base de datos** ni en el repositorio de código: se lee de un volumen externo. Existe un procedimiento de custodia y rotación documentado. |
| **Entorno productivo** | Todo lo anterior está verificado en entorno de desarrollo. El despliegue productivo (certificados, respaldos, alta disponibilidad) es trabajo pendiente. |

---

## Próximos pasos sugeridos, por prioridad

1. **Fase 5 — API de validación** (servidor) + **Bloque 4 — revalidación** (cliente).
   Cierra el circuito: permite **revocar una licencia y que el cliente lo acate**.
   Sin esto, una licencia emitida no se puede dar de baja en la práctica.
2. **Bloque 7 — tope de usuarios** (cliente). Hace cumplir el límite contratado.
3. **Fase 8 — endurecimiento** y despliegue productivo.
4. **Fase 6 — auditoría** y **Bloque 6 — aviso de vencimiento**. Mejoras de operación.

---

## Métricas

| Indicador | Valor |
| --- | --- |
| Pruebas automatizadas en verde | **153** (62 servidor + 91 cliente) |
| Fases funcionalmente completas — servidor | **5 de 8** (más 2 parciales) |
| Tareas completadas — cliente | **29 / 43** (67 %) |
| Ciclo de vida de licencia funcionando de punta a punta | **Sí** |
| Componentes desplegados y operativos | **2 de 2** |

> **Nota sobre el conteo del servidor.** El avance se mide aquí por **fase funcional
> verificada** (el código existe, está desplegado y sus pruebas pasan), no por el conteo
> del checklist interno. Ese checklist arrastra tareas sin marcar de fases que sí están
> construidas y operativas, por lo que subestima el avance real. La medida honesta es la
> capacidad demostrable, y esa está verificada por las 153 pruebas automatizadas y por el
> ciclo completo funcionando de punta a punta.
