# Guion de demo — Licenciamiento

**Antes de empezar:** corré `./scripts/demo/arrancar-demo.sh`. Si no dice TODO VERDE, no arranques.

**URL:** http://localhost:8000 → entrá a `/license/activation`

---

## La idea que tienen que llevarse

> Una licencia es un **token firmado criptográficamente**. La aplicación la verifica
> **sin conectarse a ningún servidor**. No se puede falsificar, no se puede editar,
> y no sirve copiada en otra instalación.

Todo lo demás es detalle. Si se llevan solo eso, ganaste.

---

## Acto 1 — La licencia legítima

Mostrás la pantalla con la licencia activa: cliente `reportesia`, 25 usuarios, válida hasta 2027.

> "El sistema está licenciado. Esta licencia dice quién es el cliente, cuántos
> usuarios puede tener y hasta cuándo vale."

---

## Acto 2 — "¿Y si me la copio y la uso en mi empresa?"

Es LA pregunta que alguien va a hacer. Adelantate vos y hacéla en voz alta.

Pegá este token. Está **perfectamente firmado por nosotros** — es legítimo. Pero fue
emitido para `empresa-pirata.com`:

```
eyJ0eXAiOiJKV1QiLCJhbGciOiJFZERTQSIsImtpZCI6ImRldi1rMSJ9.eyJjbGllbnRfaWQiOiJyZXBvcnRlc2lhIiwiZG9tYWluIjoiZW1wcmVzYS1waXJhdGEuY29tIiwidmFsaWRfZnJvbSI6IjIwMjYtMDctMTNUMDA6NTY6MTIrMDA6MDAiLCJ2YWxpZF91bnRpbCI6IjIwMjctMDctMTNUMDA6NTY6MTIrMDA6MDAiLCJtYXhfdXNlcnMiOjI1LCJpc3N1ZWRfYXQiOiIyMDI2LTA3LTEzVDAwOjU2OjEyKzAwOjAwIiwic2NoZW1hX3ZlcnNpb24iOjEsImtpZCI6ImRldi1rMSJ9.cVr9EXFXVoKNI9JVQVbziL2_Rzcq3XcTsNBUT8yNpy8dKfuS-IdoB-f1QLgPp-bNlEXBRxx3zUUJFt-7K56OBA
```

**Resultado:** `El dominio de la licencia (empresa-pirata.com) no coincide con el host de la aplicación (localhost).`

> "La licencia está **atada al dominio**. Copiarla no sirve de nada."

Y un detalle que vale la pena señalar: **la licencia buena sigue intacta**. Un token
inválido no te tira abajo el sistema. Se verifica primero y se guarda después — nunca
al revés.

---

## Acto 3 — El golpe final: "Entonces la edito"

> "Bueno, entonces le cambio el dominio, me pongo 10.000 usuarios y vencimiento en 2099."

Agarrá el token **legítimo** y cambiale **un solo carácter del final** (termina en `...DHWDA`,
poné `...DHWDX`):

```
eyJ0eXAiOiJKV1QiLCJhbGciOiJFZERTQSIsImtpZCI6ImRldi1rMSJ9.eyJjbGllbnRfaWQiOiJyZXBvcnRlc2lhIiwiZG9tYWluIjoibG9jYWxob3N0IiwidmFsaWRfZnJvbSI6IjIwMjYtMDctMTNUMDA6MjY6NTkrMDA6MDAiLCJ2YWxpZF91bnRpbCI6IjIwMjctMDctMTNUMDA6MjY6NTkrMDA6MDAiLCJtYXhfdXNlcnMiOjI1LCJpc3N1ZWRfYXQiOiIyMDI2LTA3LTEzVDAwOjI2OjU5KzAwOjAwIiwic2NoZW1hX3ZlcnNpb24iOjEsImtpZCI6ImRldi1rMSJ9.qglDFKHvxA3INz8Kyqs2R4WI9WTVR9lPjhimmHRRm5SQU_bECFTDWyEfnhag795OlXo-eoFbpOv_hJUELdHWDX
```

**Resultado:** `Signature verification failed`

Y acá parás, y explicás:

> "El token está firmado con **Ed25519**. La firma es una operación matemática sobre el
> contenido exacto. Tocás un bit y la firma deja de cerrar.
>
> Para generar una firma nueva que sí cierre, **necesitás la clave privada** — y esa vive
> solo en nuestro servidor. Nunca sale de ahí.
>
> **Esto no es un `if` en el código.** Un `if` lo parcheás. Esto no lo parcheás sin
> romper criptografía de curva elíptica."

---

## Volver a dejar todo bien

Pegá el token legítimo original (el que termina en `...DHWDA`, el del Acto 3 **sin** modificar).

---

## Si preguntan "¿dónde se generan las licencias?"

Decí la verdad:

> "En un servidor de licencias que estamos construyendo — Java, WildFly, Postgres.
> Ya tiene la capa criptográfica funcionando: genera claves Ed25519, firma tokens,
> publica su JWKS y rota claves. Le falta el portal de administración.
>
> Mientras tanto los tokens se firman con una herramienta de desarrollo que hace
> **exactamente lo mismo**: mismo algoritmo, mismo formato, misma seguridad."

Y remátalo con lo que en realidad es una **fortaleza de la arquitectura**:

> "Y fijate que el cliente funciona **sin el servidor**. Eso es a propósito: si el
> servidor se cae, ningún cliente deja de funcionar. Por eso pudimos construirlo y
> demostrarlo antes de que el servidor exista."

Si querés mostrar que el servidor existe de verdad: **http://localhost:9990**
(usuario `admin`, password `Admin#12345`) — la consola de WildFly con el WAR desplegado.

---

## Lo que NO tenés que hacer

- **No prometas** un portal de administración funcionando. Todavía no existe.
- **No inventes** números de rendimiento ni fechas de entrega en el aire.
- Si algo falla en vivo: decilo, mostrá el error y explicá por qué pasó.
  Un error explicado enseña más que una demo perfecta.
