# Corrección del par de ejemplo — Tipo de reporte 4 "Plan agro"

> Objetivo: que las generaciones del tipo 4 produzcan un **análisis técnico rico**
> (estilo del cliente), partiendo de una batería cruda.

## Por qué se corrige

El par de ejemplo del tipo 4 estaba cargado **AL REVÉS** (error de la migración del tipo 3 → 4):

| Slot | Estaba (MAL) | Debe estar (BIEN) |
|------|--------------|-------------------|
| ENTRADA | `ANÁLISIS GENERAL POSTCOSECHA` (un análisis rico, 3.078 palabras) | una **batería cruda** (~200 palabras) |
| SALIDA  | `RESIDUOS` (corto, off-topic, 156KB son 1 imagen) | un **análisis rico** (1.665 palabras, texto puro) |

El few-shot aprende `ENTRADA → SALIDA`. Con el par invertido, el modelo aprendía a
**achicar y cambiar de tema**. Con el par correcto, aprende a **EXPANDIR** una batería
en un análisis rico — que es lo que pide el cliente.

## Regla de oro del par de ejemplo

- **ENTRADA** = batería cruda (OBJETIVO + ACTIVIDADES), pocas palabras.
- **SALIDA**  = análisis rico, MISMO TEMA que la entrada, sin imágenes pesadas.
- El "parecido" se mide en **TEXTO**, nunca en peso de archivo (una imagen embebida
  infla los KB pero la IA no genera imágenes).

## Archivos de esta carpeta (par RIEGO — coherente y verificado)

- `ENTRADA/OBJETIVO Bateria ARO Sistema de riego FEBDIC 2022_lib.docx`  (~90 palabras)
- `ENTRADA/ACTIVIDADES Bateria ARO Sistema de riego FEBDIC 2022_lib.docx`  (~112 palabras)
- `SALIDA/ANÁLISIS PLAN DE MANEJO EFICIENTE ... SISTEMA DE RIEGO ...docx`  (1.665 palabras, texto puro, 0 imágenes → entra completo en el cap del few-shot de 16K)

## Pasos para aplicar (panel admin)

1. Entrar a `https://mfg.blmovil.com/admin/report-files/4`.
2. **Eliminar** el par actual: la entrada `ANÁLISIS GENERAL POSTCOSECHA` y la salida `RESIDUOS`.
3. **Subir Archivos** → cargar como **Archivo de Entrada** los 2 de `ENTRADA/`.
4. Cargar como **Archivo de Salida** el de `SALIDA/`.
5. Ir a `https://mfg.blmovil.com/admin/ai-training/4` y **Re-entrenar** el tipo 4.
   (El re-entreno además aplica el refuerzo de estilo de salida desplegado el 29/05).
6. **Probar**: en `/admin/ai-training/4/generate` subir como entrada la batería de riego
   → debe salir un análisis rico de ~1.500 palabras con el estilo del cliente.

## Validar el resultado

- Badge verde **✓ Validación OK**.
- Longitud ~1.500 palabras (no 300–400 como antes).
- Estructura de análisis multi-sección, NO un calco de la entrada ni un resumen de residuos.
- `Ctrl+F` palabras prohibidas → no aparecen.
