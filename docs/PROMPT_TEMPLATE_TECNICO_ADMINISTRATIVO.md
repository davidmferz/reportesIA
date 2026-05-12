# Prompt template — Proyecto técnico-administrativo

Plantilla de prompt para usar en el campo **Prompt** de un `ReportType`
cuando se quiere generar un documento técnico-administrativo con
desarrollo profundo (≈25-30 páginas), tomando como entrada un brief
con datos del proyecto.

## Contexto

Una versión anterior del prompt incluía las reglas:

- *"No agregar información innecesaria"*
- *"No ampliar contexto fuera de lo solicitado"*
- *"Introducción máximo 300 palabras"*

Estas reglas, combinadas con la jerarquía del sistema en
`AITrainingService::buildEnhancedSystemPrompt` (*"Las instrucciones del
cliente tienen MÁXIMA prioridad y NUNCA pueden ser contradichas"*),
hacían que el modelo produjera un **eco estructurado** del input: las
mismas secciones del brief reorganizadas, sin desarrollo técnico de
cada tema. El few-shot example (Salida ejemplo de 25-30 páginas)
quedaba subordinado a las reglas y no se imitaba en profundidad.

Esta plantilla corrige esa contradicción autorizando explícitamente
desarrollo técnico, conocimiento del dominio y extensión equivalente
al ejemplo.

## Prompt recomendado (copiar/pegar en el campo Prompt)

```text
Desarrolla un proyecto técnico–administrativo con redacción profesional, objetiva, estructurada y técnicamente profunda.

ENFOQUE Y PROFUNDIDAD:
- Toma los datos del archivo de entrada como punto de partida, NO como techo.
- Desarrolla CADA sección con profundidad técnica: contexto del dominio, fundamento técnico-operativo, justificación, fases, infraestructura/equipos involucrados, métricas y consecuencias.
- Apóyate en conocimiento del giro/industria correspondiente (procesos, biología, normativa, buenas prácticas) para fundamentar técnicamente cada apartado.
- La extensión y nivel de detalle deben ser equivalentes a un documento técnico de 25–30 páginas (≥6000 palabras).
- Cada sección debe ser autocontenida y aportar valor más allá de reformular el contenido del input.

ESTILO Y RESTRICCIONES:
- Redacción profesional, objetiva y técnica.
- Evita lenguaje comercial, promocional o ambiguo.
- Las actividades deben ser reales, medibles y coherentes con el objetivo.
- Cifras cuantitativas: usa las del input cuando existan; en su ausencia, usa referencias técnicas estándar del sector. No inventes datos específicos del cliente.

DATOS DEL PROYECTO:
- Nombre:
- Giro:
- Área:
- Problema detectado:
- Objetivo general:
- Alcance:
- Procesos involucrados:

GENERAR (con desarrollo EXTENSO en cada apartado):
1. Introducción contextualizada del dominio y del proyecto (≥600 palabras).
2. Objetivo general (con fundamentación).
3. Objetivos específicos (cada uno justificado técnicamente).
4. Desarrollo técnico: describir a profundidad el dominio, sus procesos, fases, infraestructura/equipos involucrados y puntos críticos.
5. Actividades operativas: descripción técnica de cada actividad (qué, cómo, por qué, recursos), no solo enumeración.
6. Indicadores: cada uno con definición, método de cálculo/verificación, periodo y criterio de aceptación.
7. Conclusión técnica fundamentada en lo expuesto.
```

## Resultado de validación

Probado con un caso real (plan de mantenimiento postcosecha de berries,
brief de ~250 palabras → documento técnico esperado de ~31 páginas).

| Métrica                | Prompt anterior | Prompt nuevo |
| ---------------------- | --------------: | -----------: |
| Palabras generadas     |             304 |    **7,418** |
| Tamaño del output (KB) |             2.0 |       **48** |
| Tiempo (gpt-5-mini)    |            79 s |       129 s  |
| Tokens completion      |          10,546 |      10,689  |

Verificación del feedback que motivó el cambio:

| Crítica original                                                | Estado |
| --------------------------------------------------------------- | ------ |
| "Sigue mostrando el complemento del documento de entrada"       | OK     |
| "Presenta los mismos apartados sin desarrollo específico"       | OK     |
| "Sin desarrollo específico de cada tema"                        | OK     |

Aporte técnico que el modelo agregó (no estaba en el input):

- Rangos térmicos por variedad (arándano 0–2 °C, fresa 0–1 °C, HR 90–95 %).
- Patógenos relevantes (*Botrytis spp.*).
- Métodos de preenfriamiento: aire forzado vs. hydrocooling vs. cámara.
- Metodología RPN para clasificación de criticidad de activos.
- Fórmulas de KPIs (disponibilidad, MTTR, cumplimiento preventivo).
- Referencias normativas (BPM, LOTO, MSDS, EPP).

## Cómo aplicarlo en producción

1. Login como admin.
2. Editar el `ReportType` correspondiente (o crear uno nuevo).
3. Pegar el bloque de "Prompt recomendado" en el campo **Prompt**.
4. Dejar `modo_estricto = false` (los few-shot examples ayudan al estilo).
5. Asegurar al menos un par de ejemplo Entrada/Salida cargado en archivos del tipo.
6. Modelo sugerido: `gpt-5-mini` (default). Para casos especialmente largos o complejos, `gpt-5`.

## Mantenimiento de la plantilla

Si el equipo edita esta plantilla, registrar el cambio aquí:

- 2026-05-11 — Versión inicial. Reemplaza al prompt con caps de palabras y
  reglas de "no ampliar contexto", que producía outputs sin desarrollo
  técnico.
