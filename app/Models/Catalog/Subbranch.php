<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subbranch extends Model
{
    protected $table = 'catalog_subbranches';

    protected $fillable = ['catalog_branch_id', 'nombre'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'catalog_branch_id');
    }

    public function specialties(): HasMany
    {
        return $this->hasMany(Specialty::class, 'catalog_subbranch_id')->orderBy('nombre');
    }
}
