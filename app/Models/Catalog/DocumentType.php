<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentType extends Model
{
    protected $table = 'catalog_document_types';

    protected $fillable = ['catalog_service_type_id', 'nombre'];

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class, 'catalog_service_type_id');
    }
}
