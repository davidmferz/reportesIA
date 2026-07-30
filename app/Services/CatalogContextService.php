<?php

namespace App\Services;

use App\Models\Catalog\Branch;
use App\Models\Catalog\DocumentType;
use App\Models\Catalog\Sector;
use App\Models\Catalog\ServiceType;
use App\Models\Catalog\Specialty;
use App\Models\Catalog\Subbranch;

/**
 * Traduce la clasificación del catálogo a contexto que la generación puede usar:
 * un mensaje de ENCUADRE para el prompt, y las expectativas de formato que el
 * Excel declara para el entregable elegido.
 *
 * Dos decisiones de diseño que no son negociables:
 *
 * 1. El mensaje va en su PROPIO mensaje de sistema, nunca dentro del Prompt
 *    Maestro. El maestro es verbatim por exigencia del cliente; interpolarlo
 *    rompería ese contrato. Se suma como buildOutputFormatMessage() o las
 *    palabras prohibidas.
 *
 * 2. El encuadre da vocabulario y desambiguación, NO licencia para aportar
 *    hechos. La causa raíz documentada de las "alucinaciones" es justamente el
 *    mismatch de dominio: decirle al modelo "esto es Pesca" lo tienta a rellenar
 *    con normativa e indicadores pesqueros que no están en la entrada. Solo
 *    cuando el tipo de reporte ya otorgó el PERMISO DE CONOCIMIENTO el encuadre
 *    cambia de rol: pasa a ANCLAR ese permiso a un dominio concreto, en vez de
 *    que el modelo lo adivine leyendo la entrada.
 */
class CatalogContextService
{
    /**
     * Niveles de dominio, de padre a hijo, con el modelo que resuelve el nombre.
     *
     * @var array<string, class-string<\Illuminate\Database\Eloquent\Model>>
     */
    private const DOMINIO = [
        'catalog_sector_id' => Sector::class,
        'catalog_branch_id' => Branch::class,
        'catalog_subbranch_id' => Subbranch::class,
        'catalog_specialty_id' => Specialty::class,
    ];

    /**
     * Niveles del entregable: qué servicio y qué documento se produce.
     *
     * @var array<string, class-string<\Illuminate\Database\Eloquent\Model>>
     */
    private const ENTREGABLE = [
        'catalog_service_type_id' => ServiceType::class,
        'catalog_document_type_id' => DocumentType::class,
    ];

    /**
     * Mensaje de sistema con el encuadre de clasificación.
     *
     * Devuelve null cuando no hay nada que encuadrar: sin clasificación, la
     * generación se comporta exactamente como antes (cero mensajes extra, cero
     * tokens extra).
     *
     * @param  array<string, int|string|null>  $selection  Selección de las seis columnas.
     * @param  bool  $usaConocimientoModelo  Si el tipo de reporte ya autorizó aportar conocimiento del dominio.
     */
    public function promptMessage(array $selection, bool $usaConocimientoModelo = false): ?string
    {
        $dominio = $this->ruta(self::DOMINIO, $selection);
        $entregable = $this->ruta(self::ENTREGABLE, $selection);

        if ($dominio === '' && $entregable === '') {
            return null;
        }

        $lineas = ['CLASIFICACIÓN DEL PROYECTO (encuadre para esta generación; no es fuente de datos):'];

        if ($dominio !== '') {
            $lineas[] = "Dominio: {$dominio}";
        }

        if ($entregable !== '') {
            $lineas[] = "Entregable: {$entregable}";
        }

        $lineas[] = '';
        $lineas[] = 'Usala para elegir la terminología y las convenciones del sector, desambiguar '
            . 'siglas y términos de la entrada que admitan varias lecturas, y fijar el registro '
            . 'adecuado del documento.';
        $lineas[] = '';
        $lineas[] = $usaConocimientoModelo
            ? $this->anclajeDeConocimiento($dominio)
            : $this->prohibicionDeHechos();

        $orientacion = $this->orientacion($selection);

        if ($orientacion !== null) {
            $lineas[] = '';
            $lineas[] = $orientacion;
        }

        return implode("\n", $lineas);
    }

    /**
     * Expectativas de formato que el Excel declara para el entregable elegido.
     * Alimentan la validación de la salida (OutputValidatorService).
     *
     * @param  array<string, int|string|null>  $selection
     * @return array<string, string>
     */
    public function expectations(array $selection): array
    {
        $documento = $this->documentType($selection);

        if ($documento === null) {
            return [];
        }

        return array_filter([
            'requiere_tablas' => $documento->requiere_tablas,
            'requiere_formatos' => $documento->requiere_formatos,
            'requiere_diagrama' => $documento->requiere_diagrama,
        ], fn ($valor) => $valor !== null && $valor !== '');
    }

    /**
     * Sin permiso de conocimiento, el encuadre es estrictamente léxico: nombrar
     * el dominio no habilita a traerlo. Se remite a la regla del maestro para
     * los datos faltantes en vez de inventar una nueva.
     */
    private function prohibicionDeHechos(): string
    {
        return 'LÍMITE: el encuadre NO autoriza incorporar hechos, cifras, normativa, indicadores '
            . 'ni conclusiones propias del dominio que no estén en los documentos de entrada. '
            . 'Si un dato necesario no aparece en la entrada, aplicá la regla del documento: '
            . 'dejá el apartado con [Información no disponible] o indicalo en las observaciones.';
    }

    /**
     * Con permiso ya otorgado, el aporte de conocimiento existe igual. El encuadre
     * solo evita que el modelo deduzca el dominio de la entrada y termine trayendo
     * el campo equivocado.
     */
    private function anclajeDeConocimiento(string $dominio): string
    {
        $ancla = $dominio !== '' ? $dominio : 'la clasificación indicada';

        return "ANCLAJE DEL PERMISO DE CONOCIMIENTO: el aporte de conocimiento general que ya "
            . "tenés autorizado debe corresponder a {$ancla}, no al dominio que infieras por tu "
            . 'cuenta leyendo la entrada. Sigue vigente la regla innegociable: nunca inventes '
            . 'datos específicos del cliente (cifras, nombres, fechas, mediciones o resultados) '
            . 'que no estén en los documentos de entrada.';
    }

    /**
     * Configuración sugerida por el Excel para la combinación servicio+documento.
     *
     * Se declara explícitamente subordinada al caso de referencia porque el maestro
     * ordena "No cambies la estructura del documento / No añadas apartados nuevos":
     * si el Excel pide una tabla que el caso de referencia no tiene, gana el caso.
     */
    private function orientacion(array $selection): ?string
    {
        $documento = $this->documentType($selection);

        if ($documento === null) {
            return null;
        }

        $configuracion = $documento->configuracionSugerida();

        if (empty($configuracion)) {
            return null;
        }

        $items = [];
        foreach ($configuracion as $etiqueta => $valor) {
            $items[] = "- {$etiqueta}: {$valor}";
        }

        return "ORIENTACIÓN DEL CATÁLOGO para este entregable (metodología del Excel del generador):\n"
            . implode("\n", $items)
            . "\nEs orientación, no una orden. Si algo de acá contradice al CASO DE REFERENCIA, "
            . 'gana el caso de referencia: no cambies su estructura y no agregues apartados que '
            . 'no existan en él.';
    }

    /**
     * @param  array<string, class-string<\Illuminate\Database\Eloquent\Model>>  $niveles
     * @param  array<string, int|string|null>  $selection
     */
    private function ruta(array $niveles, array $selection): string
    {
        $nombres = [];

        foreach ($niveles as $columna => $modelo) {
            $id = $selection[$columna] ?? null;

            if ($id === null || $id === '') {
                // Los niveles cuelgan del anterior: sin padre no hay hijo que mostrar.
                break;
            }

            $nombre = $modelo::find($id)?->nombre;

            if ($nombre === null) {
                break;
            }

            $nombres[] = $nombre;
        }

        return implode(' > ', $nombres);
    }

    private function documentType(array $selection): ?DocumentType
    {
        $id = $selection['catalog_document_type_id'] ?? null;

        return $id === null || $id === '' ? null : DocumentType::find($id);
    }
}
