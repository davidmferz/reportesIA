<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un apartado de la estructura sugerida de un tipo de documento.
 */
class DocumentSection extends Model
{
    protected $table = 'catalog_document_sections';

    protected $fillable = ['catalog_document_type_id', 'orden', 'apartado', 'contenido'];

    protected $casts = ['orden' => 'integer'];

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'catalog_document_type_id');
    }
}
