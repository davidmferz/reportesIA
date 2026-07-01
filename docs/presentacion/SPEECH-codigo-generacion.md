# 🗺️ Recorrido del código: "¿Qué pasa cuando se genera un archivo con IA?"

> Guión técnico para explicar el CÓDIGO (no la interfaz) del flujo de generación.
> Seguilo con el editor abierto: cada parada tiene su `archivo:línea` para clickear e ir.
>
> **Concepto de arquitectura central:** el controlador NO piensa, solo recibe y delega.
> Toda la lógica pesada vive en los **Services**. Controlador delgado, servicio gordo
> (*separación de responsabilidades*). Si mañana generás desde una API o un comando de
> consola, reusás el MISMO servicio sin tocar nada.

---

## El mapa de carpetas (dónde vive cada cosa)

```
reportesIA/
├── routes/web.php                         ← El PORTERO: mapea URL → método
├── app/
│   ├── Http/Controllers/
│   │   └── AITrainingController.php        ← El RECEPCIONISTA: recibe el request HTTP
│   ├── Services/                           ← EL CEREBRO (acá está toda la lógica)
│   │   ├── AITrainingService.php           ← ORQUESTADOR (el jefe de todo)
│   │   ├── DocumentExtractorService.php    ← Lee PDF/Word/Excel → texto plano
│   │   ├── OutputValidatorService.php      ← El SUPERVISOR de calidad
│   │   ├── PromptParserService.php         ← Lee las reglas del prompt (regex)
│   │   └── WebResearchService.php          ← Busca en internet (opcional)
│   └── Models/
│       ├── AIGeneration.php                ← 1 fila por cada generación
│       ├── AITraining.php                  ← El entrenamiento (system_prompt)
│       └── ForbiddenWord.php               ← Palabras prohibidas globales
└── storage/app/ai_generations/{id}/        ← Acá se guardan los archivos subidos
```

---

## PARADA 1 — El portero: `routes/web.php:78`

```php
Route::post('ai-training/{reportType}/generate', [AITrainingController::class, 'generate'])
```

Cuando el usuario aprieta "Generar", el navegador manda un `POST`. Laravel mira esta
tabla de rutas y dice: "esto va al método `generate()` del `AITrainingController`".
Nada más. Es un conmutador telefónico.

---

## PARADA 2 — El recepcionista: `AITrainingController.php:125`

El controlador hace 4 cosas y **NINGUNA es inteligencia artificial todavía**:

1. **Valida el request** (`:127`) — ¿mandaron un capítulo? ¿archivos? ¿pesan < 50MB?
2. **Crea la fila en la BD** (`:159`) con `status => 'processing'`. CLAVE: la generación
   queda registrada ANTES de empezar. Si algo explota, tenemos rastro.
3. **Guarda los archivos subidos en disco** (`:172-188`) en
   `storage/app/ai_generations/{id}/` y extrae el texto de cada uno.
4. **Delega al cerebro** (`:194`):

```php
$result = $this->trainingService->generateOutput($training, $inputFiles, $modeloOverride);
```

El controlador dice "yo no sé cómo se genera, que lo haga el servicio". Le pasa el
entrenamiento, los archivos y el modelo. Y espera el resultado.

---

## PARADA 3 — El corazón: `AITrainingService::generateOutput()` — `:416`

El método más importante del sistema. Desglosado en fases:

### Fase A — Extraer texto de los archivos (`:429-436`)
Cada PDF/Word/Excel se convierte a texto plano con el `DocumentExtractorService`.
La IA no lee PDFs, lee texto.

### Fase B — Matemática de tokens (`:438-468`)
Los modelos de IA tienen un límite de "memoria" (contexto). El código calcula:

```
espacio_para_ejemplos = contexto_total − system_prompt − entrada − reserva_salida − margen
```

"Tengo 400.000 tokens de espacio, ya gasté tanto en instrucciones y tanto en la
entrada... ¿cuánto me queda para ejemplos de referencia?" Sin esta cuenta, la IA
revienta con "context length exceeded".

### Fase C — Armar la conversación (`:470-594`)
Se construye el array `$messages` — LITERALMENTE lo que se le manda a OpenAI.
Se arma por capas, como una lasaña:

| Capa | Línea | Qué es |
|------|-------|--------|
| `system` | `:471-476` | El **system_prompt** (lo que aprendió en el entrenamiento) |
| `system` | `:478-483` | **Palabras prohibidas globales** (defense-in-depth) |
| `system` | `:490-505` | Permiso de conocimiento del modelo *(si el toggle está ON)* |
| `system` | `:511-524` | Datos de internet *(si el toggle está ON)* |
| `user/assistant` | `:543-560` | Los **ejemplos few-shot** (pares de referencia) |
| `user` | `:591-594` | **La entrada real del usuario** (los datos a procesar) |

> **Concepto imprescindible:** la IA no "recuerda" nada entre llamadas. Cada vez le
> mandás TODA la conversación de cero — instrucciones + ejemplos + datos. Por eso se
> arma este array gigante en cada generación.

### Fase D — El loop de auto-corrección (`:610-674`) 🌟

Un `for` que corre hasta 3 veces (intento 0 + 2 reintentos):

```php
for ($attempt = 0; $attempt <= $this->maxValidationRetries; $attempt++) {
    // 1. Llamar a OpenAI
    $response = OpenAI::chat()->create([...]);          // :613

    // 2. ¿Vino vacío? Error claro y salimos                :633
    // 3. Validar la respuesta
    $validation = $this->validator->validate(...);      // :650

    // 4. ¿Pasó? Listo, salimos del loop
    if ($validation['valid']) break;                    // :661

    // 5. ¿No pasó? Le devolvemos el error a la IA y reintenta
    $messages[] = ['role' => 'assistant', 'content' => $generatedContent];         // :672
    $messages[] = ['role' => 'user', 'content' => $validation['feedback_for_ai']]; // :673
}
```

En `:672-673` **el sistema le contesta a la IA**: "che, tu respuesta anterior usó una
palabra prohibida, corregila". Y vuelve a preguntar. Es una conversación automática
entre nuestro código y la IA.

### Fase E — Las garantías determinísticas (`:679-703`) 🔒

La cita del código lo dice todo (`:691`):
> *"el LLM es probabilístico, el código es determinístico"*

Aunque la IA falle tras 2 reintentos, el código NO confía y corrige él mismo:
- **`sanitizeForbiddenWords()`** (`:679`) — remueve palabras prohibidas con regex, a mano.
- **`truncateToWordLimit()`** (`:696`) — si se pasó del límite, corta en la última
  oración completa.

> **Punto filosófico para la reunión:** *"A la IA le pedimos amablemente que cumpla las
> reglas. Pero como es probabilística, el código pone una red de seguridad que garantiza
> el cumplimiento. No confiamos, verificamos."*

### Fase F — Devolver el resultado (`:705-721`)
Un array con: contenido final, tokens gastados, resultado de validación, historial de
intentos, y un snapshot del prompt enviado (para auditoría).

---

## PARADA 4 — De vuelta en el controlador: guardar todo (`AITrainingController.php:196-212`)

El controlador recibe el resultado y actualiza la fila de la BD:
- `status => 'completed'`
- el contenido generado
- los tokens (para calcular costo)
- el resultado de validación completo
- el snapshot del prompt

Y redirige al usuario a ver su documento. **Fin del viaje.**

---

## PARADA 5 — El supervisor de calidad: `OutputValidatorService.php:26`

El guardián. El método `validate()` (`:26`) NO usa IA — es puro código determinístico.
Chequea:

1. **Palabras prohibidas** (`:42-51`) — busca coincidencias en el texto.
2. **Máximo de palabras** (`:53-61`) — ¿se pasó?
3. **Mínimo de palabras** (`:63-70`) — ¿se quedó corto?

Si algo falla, arma el `feedback_for_ai` — el mensaje que se le devuelve a la IA en el
loop de la Fase D.

La cabecera (`:6-8`) resume la filosofía de todo el sistema:
> *"Trabaja en modo determinístico: NO depende de la IA para validar... Esto cierra la
> brecha probabilística del LLM."*

---

## 🎯 El resumen de 30 segundos

> "Un POST entra por `routes/web.php`, cae en el método `generate()` del controlador,
> que valida, guarda los archivos y **delega** al `AITrainingService`. Ese servicio
> extrae el texto, calcula el presupuesto de tokens, arma la conversación por capas, y
> entra en un **loop de generación-validación-corrección** de hasta 3 vueltas contra
> OpenAI. Al final, aunque la IA falle, el código aplica **saneo y truncado
> determinístico** como red de seguridad. El resultado se persiste en `ai_generations`
> con tokens, validación y auditoría. El controlador es delgado; toda la inteligencia
> vive en los Services."

---

## 🧠 Detalles técnicos finos (por si preguntan a fondo)

- **Modelo por defecto:** `gpt-5-mini` (`AITrainingService.php:19`).
- **Reintentos máximos:** 2 (`:47`) — más da rendimientos decrecientes.
- **`set_time_limit(600)`** (`:425`) — GPT-5/o1/o3 razonan 60-180s; el default de PHP
  (30s) mataría el request.
- **Adaptación por modelo de razonamiento** (`:72-102`):
  - `tokenLimitParam()` — GPT-5/o1/o3 usan `max_completion_tokens`; GPT-4 usa `max_tokens`.
  - `samplingParams()` — GPT-5/o1/o3 NO aceptan `temperature`/`top_p`; GPT-4 sí
    (`temperature=0.1`, `top_p=0.9` para salida más determinística).
- **Modo estricto** (`:530`) — excluye los few-shot: pesan más que las instrucciones y
  arrastran a la IA hacia patrones aprendidos que el cliente rechaza.
- **Web search fail-safe** (`:511-524`) — si la búsqueda falla, la generación continúa
  igual, sin datos externos.

---

## 💡 Consejo para la demo en vivo

NO scrollees las 1087 líneas del servicio. Mostrá SOLO 3 momentos:
1. El array de `$messages` (Fase C) — cómo se arma el pedido por capas.
2. El `for` del loop (Fase D) — la auto-corrección.
3. El saneo determinístico (Fase E) — la red de seguridad.

Esos tres pedazos cuentan TODA la historia. Lo demás es ruido para la audiencia.
