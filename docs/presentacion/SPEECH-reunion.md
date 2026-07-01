# 🎤 Speech: Sistema de Generación de Reportes con IA

> Guión para explicar el sistema **reportesIA** en una reunión, asumiendo que la audiencia **no tiene conocimientos técnicos previos**.
>
> **Regla de oro:** analogías primero, nombres técnicos después (y solo si hace falta). Explicás QUÉ resuelve y CÓMO, no qué clases tiene el código.

---

## 1. El gancho — El problema que resolvemos *(~30 seg)*

> "Imaginen que en una empresa hay alguien que todos los meses tiene que escribir el MISMO tipo de reporte. Un análisis financiero, un informe técnico, un reporte agrícola. Siempre la misma estructura, el mismo estilo, las mismas secciones. Solo cambian los datos.
>
> Eso es horas y horas de trabajo repetitivo. Y acá es donde entra nuestro sistema: **automatiza esa escritura usando inteligencia artificial.** Le enseñamos UNA vez cómo debe verse el reporte, y de ahí en más, lo genera solo."

**Por qué arrancás así:** todo el mundo entiende "trabajo repetitivo aburrido". Nadie necesita saber PHP para eso.

---

## 2. La analogía maestra — Cómo funciona *(~1 min)*

> "¿Cómo aprende el sistema? Igual que un empleado nuevo.
>
> Cuando entra alguien nuevo a un puesto, ¿qué hacés? Le mostrás ejemplos: 'Mirá, ASÍ se hace este reporte. Esta es la estructura, este es el tono, estas palabras NO las uses nunca.' Le das dos o tres ejemplos y el nuevo capta el patrón.
>
> Nuestro sistema hace exactamente eso. Le subimos **ejemplos** de reportes bien hechos, él **aprende el patrón**, y después genera reportes nuevos imitando ese estilo."

> 💡 *Concepto que estás enseñando sin decirlo: esto se llama **few-shot learning** (aprender de pocos ejemplos). NO uses el término salvo que alguien pregunte.*

---

## 3. El recorrido en 3 pasos *(2-3 min)*

Este es el corazón. Contalo como un viaje, en tres etapas.

### 🔧 Paso 1: Configurar el "molde"
> "Primero, el administrador crea un **Tipo de Reporte**. Es como definir un molde. Le pone un nombre —por ejemplo 'Reporte de Análisis Agrícola'— y le da instrucciones: cuántas palabras máximo, qué palabras están prohibidas, y un par de interruptores que ya les voy a contar."

### 🎓 Paso 2: Enseñarle con ejemplos (entrenamiento)
> "Después subimos los ejemplos: pares de documentos. Uno que dice 'estos son los datos crudos que entran' y otro que dice 'así tiene que quedar el resultado final'. El sistema los lee —sean PDF, Word, Excel, lo que sea— y **estudia el patrón**: qué secciones tiene, qué formato usa, cómo se estructura.
>
> Un detalle importante: en este paso NO gasta nada de inteligencia artificial. Es análisis local, en nuestro propio servidor. Barato y rápido."

### ⚡ Paso 3: Generar el reporte de verdad
> "Y acá viene la magia. El usuario sube los datos nuevos, aprieta 'Generar', y el sistema arma todo el pedido y se lo manda a la inteligencia artificial de OpenAI —la misma tecnología detrás de ChatGPT. La IA devuelve el reporte terminado, listo para descargar."

---

## 4. Control de calidad — lo que te hace quedar BIEN *(1-2 min)*

Acá está lo que diferencia un juguete de un sistema serio. **Recalcá esto**, porque es donde está el valor real.

> "Ahora, una IA sola no alcanza. ¿Por qué? Porque la IA a veces se equivoca, se va de largo, o usa palabras que el cliente prohibió. Entonces nosotros pusimos un **control de calidad automático**, como un supervisor que revisa antes de entregar.
>
> Cuando la IA genera el reporte, el sistema lo revisa solo y chequea tres cosas:
> - ¿Usó alguna **palabra prohibida**?
> - ¿Respetó el **límite de palabras**?
> - ¿Se inventó secciones que nadie pidió?
>
> Y si algo está mal, hace algo muy inteligente: **le devuelve el reporte a la IA con la corrección** —'che, usaste esta palabra que no va, arreglala'— y la IA lo rehace. Hasta dos veces. Y si después de eso todavía queda algo, el sistema lo corrige él mismo de forma automática.
>
> Resultado: un reporte que cumple las reglas, siempre."

> 🌟 *Este es tu momento de brillar. Es el que convierte "hicimos un chatbot" en "construimos un sistema con garantías de calidad".*

---

## 5. Las dos features que suenan a futuro *(~1 min)*

> "Y le sumamos dos interruptores opcionales que el cliente puede prender o apagar por cada tipo de reporte:
>
> **Primero — conexión a internet.** Si lo prendés, antes de escribir el reporte la IA busca información actualizada en la web y la incorpora. Datos frescos, no solo lo que sabía de antes.
>
> **Segundo — conocimiento experto del modelo.** Normalmente la IA se limita estrictamente a los datos del cliente. Pero si prendés este interruptor, le damos permiso para que aporte su propio conocimiento del tema y enriquezca el reporte —eso sí, sin inventar datos específicos del cliente, jamás."

---

## 6. Trazabilidad — para el que pregunta por seguridad/auditoría *(~30 seg)*

> "Por último, todo queda registrado. Cada reporte que se genera, quién lo hizo, cuándo, con qué datos, cuánto costó. Tenemos un **registro de auditoría** completo. Si mañana el cliente pregunta '¿por qué este reporte salió así?', tenemos la respuesta exacta. Nada pasa sin quedar anotado."

---

## 7. Cierre *(~15 seg)*

> "En resumen: le enseñamos una vez, genera para siempre, con control de calidad automático y todo auditado. Eso es lo que construimos. ¿Preguntas?"

---

## 🎯 Tips para el día de la reunión

| Situación | Qué hacer |
|-----------|-----------|
| "¿Qué tecnología usa?" | "Laravel para el sistema, y OpenAI para la IA. Pero lo importante no es la herramienta, es el problema que resuelve." |
| Ves caras perdidas | Volvé a la analogía del **empleado nuevo que aprende con ejemplos**. Nunca falla. |
| Alguien técnico quiere profundidad | Ahí SÍ soltás: orquestador `AITrainingService`, validación `OutputValidatorService`, extracción de documentos con PhpWord/PdfParser. |
| Te preguntan por el costo | "Se paga por cantidad de datos procesados (tokens), como cualquier servicio de IA. Cada generación te dice exactamente cuánto costó." |

---

## 🧠 Chuleta técnica (solo por si preguntan a fondo)

*No leas esto en la reunión. Es tu red de seguridad si alguien técnico presiona.*

- **Stack:** Laravel 12 (PHP 8.2+), frontend Blade + Alpine.js + Tailwind, build con Vite, base de datos SQLite (dev) / MySQL (prod).
- **IA:** SDK `openai-php/laravel`. Usa **Chat Completions** para generar y **Responses API** (herramienta `web_search`) para el toggle de internet. Modelo por defecto: `gpt-5-mini`, configurable por tipo de reporte.
- **Servicios clave:**
  - `AITrainingService` — orquestador de entrenamiento y generación (tokens, reintentos, modelos).
  - `DocumentExtractorService` — extrae texto de PDF/DOCX/XLSX/TXT/VSDX.
  - `OutputValidatorService` — validación determinística (palabras prohibidas, límites, secciones).
  - `PromptParserService` — extrae reglas del prompt del cliente (regex).
  - `WebResearchService` — búsqueda en internet, fail-safe.
- **Datos principales:** `report_types`, `ai_trainings`, `ai_training_examples`, `ai_generations`, `forbidden_words`, `activity_logs`.
- **Auditoría:** trait `LogsActivity` sobre los modelos de config y generaciones → vista en `/admin/activity-logs`.

---

> **Último consejo:** ensayalo en voz alta al menos una vez. El texto se lee lindo, pero recién cuando lo decís en voz alta te das cuenta de dónde trabás.
