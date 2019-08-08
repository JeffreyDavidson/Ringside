<?php

namespace App\Models;

use App\Enums\StableStatus;
use App\Traits\HasCachedAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Eloquent\Concerns\HasCustomRelationships;

class Stable extends Model
{
    use SoftDeletes, HasCustomRelationships, HasCachedAttributes;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

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
     * Get all wrestlers that have been members of the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function wrestlerHistory()
    {
        return $this->leaveableMorphedByMany(Wrestler::class, 'member')
            ->using(Member::class);
    }

    /**
     * Get all current wrestlers that are members of the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function currentWrestlers()
    {
        return $this->wrestlerHistory()->current();
    }

    /**
     * Get all previous wrestlers that were members of the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function previousWrestlers()
    {
        return $this->leaveableMorphedByMany(Wrestler::class, 'member')
            ->using(Member::class)
            ->detached();
    }

    /**
     * Get all tag teams that have been members of the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function tagTeamHistory()
    {
        return $this->leaveableMorphedByMany(TagTeam::class, 'member')
            ->using(Member::class);
    }

    /**
     * Get all current tag teams that are members of the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function currentTagTeams()
    {
        return $this->tagTeamHistory()->current();
    }

    /**
     * Get all previous tag teams that were members of the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function previousTagTeams()
    {
        return $this->leaveableMorphedByMany(TagTeam::class, 'member')
            ->using(Member::class)
            ->detached();
    }

    /**
     * Get the retirements of the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function retirements()
    {
        return $this->morphMany(Retirement::class, 'retiree');
    }

    /**
     * Get the current retirement of the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function retirement()
    {
        return $this->morphOne(Retirement::class, 'retiree')->whereNull('ended_at');
    }

    /**
     * Get all of the employments of the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function employments()
    {
        return $this->morphMany(Employment::class, 'employable')->whereNull('ended_at');
    }

    /**
     * Get the current employment of the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function employment()
    {
        return $this->morphOne(Employment::class, 'employable')->whereNull('ended_at');
    }

    /**
     * Get all the members of the stable.
     *
     * @return Collection
     */
    public function getMembersAttribute()
    {
        return $this->currentWrestlers->merge($this->currentTagTeams);
    }

    /**
     * Determine the status of the stable.
     *
     * @return \App\Enum\WrestlerStatus
     *
     */
    public function getStatusAttribute()
    {
        if ($this->is_bookable) {
            return StableStatus::BOOKABLE();
        }

        if ($this->is_retired) {
            return StableStatus::RETIRED();
        }

        return StableStatus::PENDING_INTRODUCTION();
    }

    /**
     * Determine if a stable is bookable.
     *
     * @return bool
     */
    public function getIsBookableAttribute()
    {
        return $this->is_employed && !($this->is_retired);
    }

    /**
     * Determine if a stable is employed.
     *
     * @return bool
     */
    public function getIsEmployedCachedAttribute()
    {
        return $this->employments()
            ->where('started_at', '<=', now())
            ->whereNull('ended_at')
            ->exists();
    }

    /**
     * Determine if a stable is retired.
     *
     * @return bool
     */
    public function getIsRetiredAttribute()
    {
        return $this->retirements()->whereNull('ended_at')->exists();
    }

    /**
     * Scope a query to only include bookable tag teams.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     */
    public function scopeBookable($query)
    {
        return $query->whereHas('employments', function (Builder $query) {
            $query->where('started_at', '<=', now())->whereNull('ended_at');
        })->whereDoesntHave('retirements', function (Builder $query) {
            $query->whereNull('ended_at');
        });
    }

    /**
     * Scope a query to only include pending introduction stables.
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
     * Scope a query to only include retired stables.
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
     * Add wrestlers to the stable.
     *
     * @param  array  $wrestlerIds
     * @return $this
     */
    public function addWrestlers($wrestlerIds)
    {
        $this->wrestlerHistory()->sync($wrestlerIds);

        return $this;
    }

    /**
     * Add tag teams to the stable.
     *
     * @param  array  $tagteamIds
     * @return $this
     */
    public function addTagTeams($tagteamIds)
    {
        $this->tagTeamHistory()->sync($tagteamIds);

        return $this;
    }

    /**
     * Activate a stable.
     *
     * @return bool
     */
    public function activate()
    {
        return $this->employments()->latest()->first()->update(['started_at' => now()]);
    }

    /**
     * Retire a stable.
     *
     * @return void
     */
    public function retire()
    {
        $this->retirements()->create(['started_at' => now()]);

        $this->currentWrestlers()->detach();
        $this->currentTagTeams()->detach();

        return $this;
    }

    /**
     * Unretire a stable.
     *
     * @return void
     */
    public function unretire()
    {
        $this->retirement()->update(['ended_at' => now()]);

        return $this;
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
