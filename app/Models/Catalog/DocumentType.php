<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    protected $table = 'catalog_document_types';

    protected $fillable = [
        'catalog_service_type_id',
        'nombre',
        'indicadores_sugeridos',
        'requiere_tablas',
        'requiere_formatos',
        'requiere_diagrama',
        'clasificacion_documental',
    ];

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class, 'catalog_service_type_id');
    }

    /**
     * Apartados de la estructura sugerida, en orden.
     */
    public function sections(): HasMany
    {
        return $this->hasMany(DocumentSection::class, 'catalog_document_type_id')->orderBy('orden');
    }

    /**
     * Configuración sugerida por el Excel, solo con lo que tenga valor.
     *
     * @return array<string, string>
     */
    public function configuracionSugerida(): array
    {
        return array_filter([
            'Indicadores sugeridos' => $this->indicadores_sugeridos,
            '¿Requiere tablas?' => $this->requiere_tablas,
            '¿Requiere formatos?' => $this->requiere_formatos,
            '¿Requiere diagrama?' => $this->requiere_diagrama,
            'Clasificación del documento' => $this->clasificacion_documental,
        ]);
    }
}
