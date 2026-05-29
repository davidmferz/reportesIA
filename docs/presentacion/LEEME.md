# Archivos para la presentación al cliente

> Los nombres de los archivos son EXACTAMENTE los que entregó el cliente. No fueron modificados.
> Login: david.melchor@blmovil.com  ·  Servidor: 74.208.135.49

---

## 1-demo-reporte-agricola/  (DEMO EN VIVO #1 — el blindado)
**Tipo de reporte:** "reporte agrícola"  ·  **Capítulo:** reporte fumigacion
- `OBJETIVO Bateria ARO Sistema de riego FEBDIC 2022_lib.docx` → subir como **archivo de entrada**.
Produce una introducción técnica (~280 palabras). Rápido (~30s), cero riesgo.

## 2-demo-plan-agro/  (DEMO EN VIVO #2 — el impactante)
**Tipo de reporte:** "Plan agro"  ·  **Capítulo:** Plan fertirriego
- `ACTIVIDADES Bateria ARO Sistema de riego FEBDIC 2022_lib.docx` → subir como **archivo de entrada**.
Produce un análisis técnico-operativo extenso (validado). Mismo tema que la demo #1 (riego ARO).

## 3-desde-cero/  (RESERVA — solo si el cliente lo exige)
Crear un tipo nuevo "Introducción demo", entrenarlo y generar. Demuestra que nada está trucado.

- `entrenamiento-ENTRADA/OBJETIVO BATT ARO - Pechtec Proc. conservacion ambiental ENEDIC 2024_lib.docx`
  → subir como **Archivo de Entrada** del par de entrenamiento (cuadro azul).
- `entrenamiento-SALIDA/INTRODUCCIÓN PROCEDIMIENTOS DE CONSERVACIÓN AMBIENTAL EN LA MINIMIZACIÓN DEL IMPACTO DE AGRO-INSUMOS EN EL CULTIVO DE ZARZAMORAS.docx`
  → subir como **Archivo de Salida** del par de entrenamiento (cuadro verde).
- `para-GENERAR/OBJETIVOBatt 2024 pectech CALICANTO Modelo de conservacion ambiental ENEDIC_lib.docx`
  → subir al **generar** (entrada distinta a la del entrenamiento: prueba que aprende y generaliza).

Modelo recomendado para el desde cero: **gpt-5-mini** (el más rápido).

---

## Reglas de oro
1. Verificá el saldo de OpenAI ANTES (causa #1 de fallo en vivo).
2. Ensayá las demos una vez la noche anterior.
3. NUNCA generes el Tipo 2 "proyecto agrónomo" en vivo (tiene errores históricos).

## Cómo validar cada resultado
- Badge verde **✓ Validación OK**.
- `Ctrl+F` y buscá las palabras prohibidas (ej. `optimizar`) → no las encuentra.
- Compará Entrada vs Salida: los datos salen de la entrada, no inventa.
