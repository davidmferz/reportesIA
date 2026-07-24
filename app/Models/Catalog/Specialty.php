<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Specialty extends Model
{
    protected $table = 'catalog_specialties';

    protected $fillable = ['catalog_subbranch_id', 'nombre'];

    public function subbranch(): BelongsTo
    {
        return $this->belongsTo(Subbranch::class, 'catalog_subbranch_id');
    }
}
