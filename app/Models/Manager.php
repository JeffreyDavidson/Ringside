<?php

namespace App\Models;

use App\Enums\ManagerStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Manager extends Model
{
    use SoftDeletes,
        HasFactory,
        Concerns\HasFullName,
        Concerns\CanBeStableMember,
        Concerns\Employable,
        Concerns\Injurable,
        Concerns\Suspendable,
        Concerns\Retirable,
        Concerns\Unguarded;

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
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', ManagerStatus::AVAILABLE);
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
     * @return void
     */
    public function updateStatus()
    {
        if ($this->isCurrentlyEmployed()) {
            if ($this->isInjured()) {
                $this->status = ManagerStatus::INJURED;
            } elseif ($this->isSuspended()) {
                $this->status = ManagerStatus::SUSPENDED;
            } elseif ($this->isAvailable()) {
                $this->status = ManagerStatus::AVAILABLE;
            }
        } elseif ($this->hasFutureEmployment()) {
            $this->status = ManagerStatus::FUTURE_EMPLOYMENT;
        } elseif ($this->isReleased()) {
            $this->status = ManagerStatus::RELEASED;
        } elseif ($this->isRetired()) {
            $this->status = ManagerStatus::RETIRED;
        } else {
            $this->status = ManagerStatus::UNEMPLOYED;
        }
    }

    /**
     * Updates a manager's status and saves.
     *
     * @return void
     */
    public function updateStatusAndSave()
    {
        $this->updateStatus();
        $this->save();
    }
}
