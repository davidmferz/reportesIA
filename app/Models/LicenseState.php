<?php

namespace App\Models;

use App\Enums\LicenseStatus;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseState extends Model
{
    use LogsActivity;

    /**
     * El JWS crudo no se vuelca al ActivityLog (evita filtrar el token firmado).
     *
     * @var list<string>
     */
    protected array $activityLogExclude = ['raw_token'];

    protected $fillable = [
        'raw_token',
        'payload',
        'valid_from',
        'valid_until',
        'max_users',
        'last_check_at',
        'last_check_result',
        'enforcement_status',
        'last_seen_at',
        'activated_at',
        'activated_by',
    ];

    protected $casts = [
        'payload' => 'array',
        'enforcement_status' => LicenseStatus::class,
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'max_users' => 'integer',
        'last_check_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    /**
     * Usuario (admin) que activó la licencia vigente.
     */
    public function activatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    /**
     * Estado de licencia vigente (modelo de una sola fila). Null si no hay
     * ninguna licencia activada todavía.
     */
    public static function current(): ?self
    {
        return static::query()->latest('id')->first();
    }
}
