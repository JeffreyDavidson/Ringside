<?php

namespace App\Models;

use App\Enums\ManagerStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class Manager extends Model
{
    use SoftDeletes;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

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
     * Get the retirements of the manager.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function retirements()
    {
        return $this->morphMany(Retirement::class, 'retiree');
    }

    /**
     * Get the current retirement of the manager.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function retirement()
    {
        return $this->morphOne(Retirement::class, 'retiree')->whereNull('ended_at');
    }

    /**
     * Get the suspensions of the manager.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function suspensions()
    {
        return $this->morphMany(Suspension::class, 'suspendable');
    }

    /**
     * Get the current suspension of the manager.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function suspension()
    {
        return $this->morphOne(Suspension::class, 'suspendable')->whereNull('ended_at');
    }

    /**
     * Get the injuries of the manager.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function injuries()
    {
        return $this->morphMany(Injury::class, 'injurable');
    }

    /**
     * Get the current injury of the manager.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function injury()
    {
        return $this->morphOne(Injury::class, 'injurable')->whereNull('ended_at');
    }

    /**
     * Get all of the employments of the manager.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function employments()
    {
        return $this->morphMany(Employment::class, 'employable');
    }

    /**
     * Get the current employment of the manager.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function employment()
    {
        return $this->morphOne(Employment::class, 'employable')->whereNull('ended_at');
    }

    /**
     * Determine the status of the manager.
     *
     * @return \App\Enum\ManagerStatus
     *
     */
    public function getStatusAttribute()
    {
        if ($this->is_bookable) {
            return ManagerStatus::BOOKABLE();
        }

        if ($this->is_retired) {
            return ManagerStatus::RETIRED();
        }

        if ($this->is_injured) {
            return ManagerStatus::INJURED();
        }

        if ($this->is_suspended) {
            return ManagerStatus::SUSPENDED();
        }

        return ManagerStatus::PENDING_INTRODUCTION();
    }

    /**
     * Determine if a manager is bookable.
     *
     * @return bool
     */
    public function getIsBookableAttribute()
    {
        return $this->is_employed && !($this->is_retired || $this->is_injured || $this->is_suspended);
    }

    /**
     * Determine if a manager is hired.
     *
     * @return bool
     */
    public function getIsEmployedAttribute()
    {
        return $this->employments()->where('started_at', '<=', now())->whereNull('ended_at')->exists();
    }

    /**
     * Determine if a manager is retired.
     *
     * @return bool
     */
    public function getIsRetiredAttribute()
    {
        return $this->retirement()->exists();
    }

    /**
     * Determine if a manager is suspended.
     *
     * @return bool
     */
    public function getIsSuspendedAttribute()
    {
        return $this->suspension()->exists();
    }

    /**
     * Determine if a manager is injured.
     *
     * @return bool
     */
    public function getIsInjuredAttribute()
    {
        return $this->injury()->exists();
    }


    /**
     * Get the full name of the manager.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return $this->first_name . ' '. $this->last_name;
    }

    /**
     * Scope a query to only include bookable managers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeBookable($query)
    {
        return $query->whereHas('employments', function (Builder $query) {
            $query->where('started_at', '<=', now())->whereNull('ended_at');
        })->whereDoesntHave('retirements', function (Builder $query) {
            $query->whereNull('ended_at');
        })->whereDoesntHave('injuries', function (Builder $query) {
            $query->whereNull('ended_at');
        })->whereDoesntHave('suspensions', function (Builder $query) {
            $query->whereNull('ended_at');
        });
    }

    /**
     * Scope a query to only include pending introduction managers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopePendingIntroduction($query)
    {
        return $query->whereHas('employments', function (Builder $query) {
            $query->where('started_at', '>', now());
        });
    }

    /**
     * Scope a query to only include retired managers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRetired($query)
    {
        return $query->whereHas('retirement');
    }

    /**
     * Scope a query to only include suspended managers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuspended($query)
    {
        return $query->whereHas('suspension');
    }

    /**
     * Scope a query to only include injured managers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInjured($query)
    {
        return $query->whereHas('injury');
    }

    /**
     * Activate a manager.
     *
     * @return bool
     */
    public function activate()
    {
        return $this->employments()->updateOrCreate(['started_at' => null], ['started_at' => now()]);
    }

    /**
     * Retire a manager.
     *
     * @return \App\Models\Retirement
     */
    public function retire()
    {
        if ($this->is_suspended) {
            $this->reinstate();
        }

        if ($this->is_injured) {
            $this->recover();
        }

        $this->retirements()->create(['started_at' => now()]);
    }

    /**
     * Unretire a manager.
     *
     * @return bool
     */
    public function unretire()
    {
        return $this->retirement()->update(['ended_at' => now()]);
    }

    /**
     * Suspend a manager.
     *
     * @return \App\Models\Suspension
     */
    public function suspend()
    {
        $this->suspensions()->create(['started_at' => now()]);
    }

    /**
     * Reinstate a manager.
     *
     * @return bool
     */
    public function reinstate()
    {
        $this->suspension()->update(['ended_at' => now()]);
    }

    /**
     * Injure a manager.
     *
     * @return \App\Models\Injury
     */
    public function injure()
    {
        $this->injuries()->create(['started_at' => now()]);
    }

    /**
     * Recover a manager.
     *
     * @return bool
     */
    public function recover()
    {
        $this->injury()->update(['ended_at' => now()]);
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        $data                 = parent::toArray();
        $data['status']       = $this->status->label();

        return $data;
    }
}
