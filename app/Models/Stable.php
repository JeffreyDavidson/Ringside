<?php

namespace App\Models;

use App\Enums\StableStatus;
use App\Models\Contracts\Activatable;
use App\Models\Contracts\CanBeDisbanded;
use App\Models\Contracts\Deactivatable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Unretirable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stable extends Model implements Activatable, CanBeDisbanded, Deactivatable, Retirable, Unretirable
{
    use SoftDeletes,
        HasFactory,
        Concerns\Activatable,
        Concerns\Deactivatable,
        Concerns\Unguarded;

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::saving(function ($stable) {
            $stable->updateStatus();
        });
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => StableStatus::class,
    ];

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
     * Get the wrestlers belonging to the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function wrestlers()
    {
        return $this->morphedByMany(Wrestler::class, 'member', 'stable_members')
                    ->using(Member::class)
                    ->withPivot(['joined_at', 'left_at']);
    }

    /**
     * Get all current wrestlers that are members of the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function currentWrestlers()
    {
        return $this->wrestlers()->whereNull('left_at');
    }

    /**
     * Get all previous wrestlers that were members of the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function previousWrestlers()
    {
        return $this->wrestlers()->whereNotNull('left_at');
    }

    /**
     * Get the tag teams belonging to the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function tagTeams()
    {
        return $this->morphedByMany(TagTeam::class, 'member', 'stable_members')
                    ->using(Member::class)
                    ->withPivot(['joined_at', 'left_at']);
    }

    /**
     * Get all current tag teams that are members of the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function currentTagTeams()
    {
        return $this->tagTeams()->whereNull('left_at');
    }

    /**
     * Get all previous tag teams that were members of the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function previousTagTeams()
    {
        return $this->tagTeams()->whereNotNull('left_at');
    }

    /**
     * Get the members belonging to the stable.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getMembersAttribute()
    {
        $wrestlers = $this->wrestlers;
        $tagTeams = $this->tagTeams;

        $members = $wrestlers->merge($tagTeams);

        return $members;
    }

    /**
     * Get all current members of the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function currentMembers()
    {
        return $this->currentTagTeams()->currentWrestlers();
    }

    /**
     * Get all previous members of the stable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function previousMembers()
    {
        return $this->previousTagTeams()->previousWrestlers();
    }

    /**
     * Update the status for the stable.
     *
     * @return void
     */
    public function updateStatus()
    {
        if ($this->isCurrentlyActivated()) {
            $this->status = StableStatus::ACTIVE;
        } elseif ($this->hasFutureActivation()) {
            $this->status = StableStatus::FUTURE_ACTIVATION;
        } elseif ($this->isDeactivated()) {
            $this->status = StableStatus::INACTIVE;
        } elseif ($this->isRetired()) {
            $this->status = StableStatus::RETIRED;
        } else {
            $this->status = StableStatus::UNACTIVATED;
        }
    }

    /**
     * Updates a stable's status and saves.
     *
     * @return void
     */
    public function updateStatusAndSave()
    {
        $this->updateStatus();
        $this->save();
    }

    /**
     * Determine if the tag team can be disbanded.
     *
     * @return bool
     */
    public function canBeDisbanded()
    {
        if ($this->isNotInActivation()) {
            return false;
        }

        return true;
    }

    /**
     * Get the retirements of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function retirements()
    {
        return $this->morphMany(Retirement::class, 'retiree');
    }

    /**
     * Get the current retirement of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function currentRetirement()
    {
        return $this->morphOne(Retirement::class, 'retiree')
                    ->where('started_at', '<=', now())
                    ->whereNull('ended_at')
                    ->limit(1);
    }

    /**
     * Get the previous retirements of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousRetirements()
    {
        return $this->retirements()
                    ->whereNotNull('ended_at');
    }

    /**
     * Get the previous retirement of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function previousRetirement()
    {
        return $this->morphOne(Retirement::class, 'retiree')
                    ->latest('ended_at')
                    ->limit(1);
    }

    /**
     * Scope a query to only include retired models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRetired($query)
    {
        return $query->whereHas('currentRetirement');
    }

    /**
     * Scope a query to include current retirement date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithCurrentRetiredAtDate($query)
    {
        return $query->addSelect(['current_retired_at' => Retirement::select('started_at')
            ->whereColumn('retiree_id', $this->getTable().'.id')
            ->where('retiree_type', $this->getMorphClass())
            ->latest('started_at')
            ->limit(1),
        ])->withCasts(['current_retired_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the model's current retirement date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByCurrentRetiredAtDate($query, $direction = 'asc')
    {
        return $query->orderByRaw("DATE(current_retired_at) $direction");
    }

    /**
     * Check to see if the model is retired.
     *
     * @return bool
     */
    public function isRetired()
    {
        return $this->currentRetirement()->exists();
    }

    /**
     * Check to see if the model has been activated.
     *
     * @return bool
     */
    public function hasRetirements()
    {
        return $this->retirements()->count() > 0;
    }

    /**
     * Determine if the model can be retired.
     *
     * @return bool
     */
    public function canBeRetired()
    {
        if ($this->isNotInActivation()) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the model can be unretired.
     *
     * @return bool
     */
    public function canBeUnretired()
    {
        if (! $this->isRetired()) {
            return false;
        }

        return true;
    }
}
