<?php

namespace App\Models;

use App\Enums\ManagerStatus;
use App\Models\Contracts\StableMember;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Manager extends SingleRosterMember implements StableMember
{
    use Concerns\HasFullName,
        Concerns\Manageables,
        Concerns\OwnedByUser,
        Concerns\StableMember,
        Concerns\Unguarded,
        HasFactory,
        SoftDeletes;

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::saving(function ($manager) {
            $manager->updateStatus();
        });
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'managers';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => ManagerStatus::class,
    ];

    /**
     * Scope a query to only include available managers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', ManagerStatus::available());
    }

    /**
     * Check to see if the manager is available.
     *
     * @return bool
     */
    public function isAvailable()
    {
        return $this->currentEmployment()->exists();
    }

    /**
     * Update the status for the manager.
     *
     * @return $this
     */
    public function updateStatus()
    {
        $this->status = match (true) {
            $this->isCurrentlyEmployed() => match (true) {
                $this->isInjured() => ManagerStatus::injured(),
                $this->isSuspended() => ManagerStatus::suspended(),
                $this->isAvailable() => ManagerStatus::available(),
            },
            $this->hasFutureEmployment() => ManagerStatus::future_employment(),
            $this->isReleased() => ManagerStatus::released(),
            $this->isRetired() => ManagerStatus::retired(),
            default => ManagerStatus::unemployed()
        };

        return $this;
    }
}
