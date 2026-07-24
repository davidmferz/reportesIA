<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $table = 'catalog_branches';

    protected $fillable = ['catalog_sector_id', 'nombre'];

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class, 'catalog_sector_id');
    }

    public function subbranches(): HasMany
    {
        return $this->hasMany(Subbranch::class, 'catalog_branch_id')->orderBy('nombre');
    }
}
