<?php

namespace App\Models;

use App\Eloquent\Concerns\HasCustomRelationships;
use App\Enums\ManagerStatus;
use Illuminate\Database\Eloquent\SoftDeletes;

class Manager extends SingleRosterMember
{
    use SoftDeletes,
        HasCustomRelationships,
        Concerns\HasFullName,
        Concerns\CanBeStableMember,
        Concerns\Unguarded;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => ManagerStatus::class,
    ];

    /**
     * Get the user belonging to the manager.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include available managers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Check to see if the model is available.
     *
     * @return bool
     */
    public function isAvailable()
    {
        return $this->currentEmployment()->exists();
    }
}
