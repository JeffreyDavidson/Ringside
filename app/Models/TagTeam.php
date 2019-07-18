<?php

namespace App\Models;

use App\Enums\TagTeamStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class TagTeam extends Model
{
    use SoftDeletes;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the wrestlers belonging to the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function wrestlers()
    {
        return $this->belongsToMany(Wrestler::class, 'tag_team_wrestler', 'tag_team_id', 'wrestler_id');
    }

    /**
     * Get the user belonging to the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the stables the tag team are members of.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function stables()
    {
        return $this->morphToMany(Stable::class, 'member');
    }

    /**
     * Get the current stable of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function stable()
    {
        return $this->morphToMany(Stable::class, 'member')->where('is_active', true);
    }

    /**
     * Get the retirements of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function retirements()
    {
        return $this->morphMany(Retirement::class, 'retiree');
    }

    /**
     * Get the current retirement of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function retirement()
    {
        return $this->morphOne(Retirement::class, 'retiree')->whereNull('ended_at');
    }

    /**
     * Get the suspensions of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function suspensions()
    {
        return $this->morphMany(Suspension::class, 'suspendable');
    }

    /**
     * Get the current suspension of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function suspension()
    {
        return $this->morphOne(Suspension::class, 'suspendable')->whereNull('ended_at');
    }

    /**
     * Get all of the employments of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function employments()
    {
        return $this->morphMany(Employment::class, 'employable')->whereNull('ended_at');
    }

    /**
     * Get the current employment of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function employment()
    {
        return $this->morphOne(Employment::class, 'employable')->whereNull('ended_at');
        // return $this->morphOne(Employment::class, 'employable')->whereNull('ended_at')->withPivot('started_at', 'ended_at');
    }

    /**
     * Determine the status of the tag team.
     *
     * @return \App\Enum\TagTeamStatus
     *
     */
    public function getStatusAttribute()
    {
        if ($this->is_bookable) {
            return TagTeamStatus::BOOKABLE();
        }

        if ($this->is_retired) {
            return TagTeamStatus::RETIRED();
        }

        if ($this->is_suspended) {
            return TagTeamStatus::SUSPENDED();
        }

        return TagTeamStatus::PENDING_INTRODUCED();
    }

    /**
     * Determine if a tag team is bookable.
     *
     * @return bool
     */
    public function getIsBookableAttribute()
    {
        return $this->is_employed && !($this->is_retired || $this->is_suspended);
    }

    /**
     * Determine if a tag team is hired.
     *
     * @return bool
     */
    public function getIsEmployedAttribute()
    {
        return $this->employments()->where('started_at', '<=', now())->whereNull('ended_at')->exists();
    }

    /**
     * Determine if a tag team is retired.
     *
     * @return bool
     */
    public function getIsRetiredAttribute()
    {
        return $this->retirements()->whereNull('ended_at')->exists();
    }

    /**
     * Determine if a tag team is suspended.
     *
     * @return bool
     */
    public function getIsSuspendedAttribute()
    {
        return $this->suspensions()->whereNull('ended_at')->exists();
    }

    /**
     * Get the combined weight of both wrestlers in a tag team.
     *
     * @return integer
     */
    public function getCombinedWeightAttribute()
    {
        return $this->wrestlers->sum('weight');
    }

    /**
     * Scope a query to only include bookable tag teams.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeBookable($query)
    {
        return $query->whereHas('employments', function (Builder $query) {
            $query->where('started_at', '<=', now())->whereNull('ended_at');
        })->whereDoesntHave('retirements', function (Builder $query) {
            $query->whereNull('ended_at');
        })->whereDoesntHave('suspensions', function (Builder $query) {
            $query->whereNull('ended_at');
        });
    }

    /**
     * Scope a query to only include pending introduced tag teams.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopePendingIntroduced($query)
    {
        return $query->whereHas('employments', function (Builder $query) {
            $query->whereNull('started_at')->orWhere('started_at', '>', now());
        });
    }

    /**
     * Scope a query to only include retired tag teams.
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
     * Scope a query to only include suspended tag teams.
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
     * Add multiple wrestlers to a tag team.
     *
     * @param  array  $wrestlers
     * @return $this
     */
    public function addWrestlers($wrestlers)
    {
        $this->wrestlers()->attach($wrestlers);

        return $this;
    }

    /**
     * Activate a tag team.
     *
     * @return bool
     */
    public function activate()
    {
        $this->employments()->latest()->first()->update(['started_at' => now()]);

        $this->wrestlers->each->activate();

        return $this;
    }

    /**
     * Retire a tag team.
     *
     * @return \App\Models\Retirement
     */
    public function retire()
    {
        if ($this->is_suspended) {
            $this->reinstate();
        }

        $this->retirements()->create(['started_at' => now()]);

        $this->wrestlers->each->retire();

        return $this;
    }

    /**
     * Unretire a tag team.
     *
     * @return bool
     */
    public function unretire()
    {
        $this->retirement()->update(['ended_at' => now()]);

        $this->wrestlers->filter->retired()->each->unretire();
    }

    /**
     * Suspend a tag team.
     *
     * @return \App\Models\Suspension
     */
    public function suspend()
    {
        $this->suspensions()->create(['started_at' => now()]);

        $this->wrestlers->each->suspend();
    }

    /**
     * Reinstate a tag team.
     *
     * @return bool
     */
    public function reinstate()
    {
        $this->suspension()->update(['ended_at' => now()]);
    }
}
