<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sector extends Model
{
    protected $table = 'catalog_sectors';

    protected $fillable = ['nombre'];

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class, 'catalog_sector_id')->orderBy('nombre');
    }
}
