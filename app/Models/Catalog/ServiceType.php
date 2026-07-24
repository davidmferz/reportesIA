<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceType extends Model
{
    protected $table = 'catalog_service_types';

    protected $fillable = ['nombre'];

    public function documentTypes(): HasMany
    {
        return $this->hasMany(DocumentType::class, 'catalog_service_type_id')->orderBy('nombre');
    }
}
