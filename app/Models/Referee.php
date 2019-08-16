<?php

namespace App\Models;

use App\Enums\RefereeStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Referee
 *
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Employment $employment
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Employment[] $employments
 * @property-read bool $is_bookable
 * @property-read bool $is_employed
 * @property-read bool $is_injured
 * @property-read bool $is_retired
 * @property-read bool $is_suspended
 * @property-read \App\Enum\RefereeStatus $status
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Injury[] $injuries
 * @property-read \App\Models\Injury $injury
 * @property-read \App\Models\Retirement $retirement
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Retirement[] $retirements
 * @property-read \App\Models\Suspension $suspension
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Suspension[] $suspensions
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Referee bookable()
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Referee injured()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Referee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Referee newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Referee onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Referee pendingIntroduced()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Referee query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Referee retired()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Referee suspended()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Referee whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Referee whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Referee whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Referee whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Referee whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Referee whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Referee withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Referee withoutTrashed()
 * @mixin \Eloquent
 */
class Referee extends Model
{
    use SoftDeletes;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get all of the employments of the referee.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function employments()
    {
        return $this->morphMany(Employment::class, 'employable')->whereNull('ended_at');
    }

    /**
     * Get the current employment of the referee.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function employment()
    {
        return $this->morphOne(Employment::class, 'employable')->whereNull('ended_at');
    }

    /**
     * Get the retirements of the referee.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function retirements()
    {
        return $this->morphMany(Retirement::class, 'retiree');
    }

    /**
     * Get the current retirement of the referee.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function retirement()
    {
        return $this->morphOne(Retirement::class, 'retiree')->whereNull('ended_at');
    }

    /**
     * Get the suspensions of the referee.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function suspensions()
    {
        return $this->morphMany(Suspension::class, 'suspendable');
    }

    /**
     * Get the current suspension of the referee.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function suspension()
    {
        return $this->morphOne(Suspension::class, 'suspendable')->whereNull('ended_at');
    }

    /**
     * Get the injuries of the referee.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function injuries()
    {
        return $this->morphMany(Injury::class, 'injurable');
    }

    /**
     * Get the current injury of the referee.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function injury()
    {
        return $this->morphOne(Injury::class, 'injurable')->whereNull('ended_at');
    }

    /**
     * Determine the status of the referee.
     *
     * @return \App\Enum\RefereeStatus
     *
     */
    public function getStatusAttribute()
    {
        if ($this->is_bookable) {
            return RefereeStatus::BOOKABLE();
        }

        if ($this->is_retired) {
            return RefereeStatus::RETIRED();
        }

        if ($this->is_injured) {
            return RefereeStatus::INJURED();
        }

        if ($this->is_suspended) {
            return RefereeStatus::SUSPENDED();
        }

        return RefereeStatus::PENDING_INTRODUCTION();
    }

    /**
     * Determine if a referee is bookable.
     *
     * @return bool
     */
    public function getIsBookableAttribute()
    {
        return $this->is_employed && !($this->is_retired || $this->is_injured || $this->is_suspended);
    }

    /**
     * Determine if a referee is hired.
     *
     * @return bool
     */
    public function getIsEmployedAttribute()
    {
        return $this->employments()->where('started_at', '<=', now())->whereNull('ended_at')->exists();
    }

    /**
     * Determine if a referee is retired.
     *
     * @return bool
     */
    public function getIsRetiredAttribute()
    {
        return $this->retirements()->whereNull('ended_at')->exists();
    }

    /**
     * Determine if a referee is suspended.
     *
     * @return bool
     */
    public function getIsSuspendedAttribute()
    {
        return $this->suspensions()->whereNull('ended_at')->exists();
    }

    /**
     * Determine if a referee is injured.
     *
     * @return bool
     */
    public function getIsInjuredAttribute()
    {
        return $this->injuries()->whereNull('ended_at')->exists();
    }

    /**
     * Get the full name of the referee.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Scope a query to only include bookable referees.
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
     * Scope a query to only include pending introduced referees.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopePendingIntroduction($query)
    {
        return $query->whereHas('employments', function (Builder $query) {
            $query->whereNull('started_at')->orWhere('started_at', '>', now());
        });
    }

    /**
     * Scope a query to only include retired referees.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRetired($query)
    {
        return $query->whereHas('retirements', function ($query) {
            $query->whereNull('ended_at');
        });
    }

    /**
     * Scope a query to only include suspended referees.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuspended($query)
    {
        return $query->whereHas('suspensions', function ($query) {
            $query->whereNull('ended_at');
        });
    }

    /**
     * Scope a query to only include injured referees.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInjured($query)
    {
        return $query->whereHas('injuries', function ($query) {
            $query->whereNull('ended_at');
        });
    }

    /**
     * Activate a referee.
     *
     * @return bool
     */
    public function activate()
    {
        return $this->employments()->latest()->first()->update(['started_at' => now()]);
    }

    /**
     * Retire a referee.
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
     * Unretire a referee.
     *
     * @return bool
     */
    public function unretire()
    {
        return $this->retirement()->update(['ended_at' => now()]);
    }

    /**
     * Injure a referee.
     *
     * @return \App\Models\Injury
     */
    public function injure()
    {
        $this->injuries()->create(['started_at' => now()]);
    }

    /**
     * Recover a referee.
     *
     * @return bool
     */
    public function recover()
    {
        $this->injury()->update(['ended_at' => now()]);
    }

    /**
     * Suspend a referee.
     *
     * @return \App\Models\Suspension
     */
    public function suspend()
    {
        $this->suspensions()->create(['started_at' => now()]);
    }

    /**
     * Reinstate a referee.
     *
     * @return bool
     */
    public function reinstate()
    {
        $this->suspension()->update(['ended_at' => now()]);
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
        $data['name']         = $this->full_name;

        return $data;
    }
}
