<?php

namespace App\Models;

use App\Enums\StableStatus;
use App\Exceptions\CannotBeRetiredException;
use App\Exceptions\CannotBeUnretiredException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stable extends Model
{
    use SoftDeletes,
        HasFactory,
        Concerns\CanBeActivated,
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

    public function addWrestlers($wrestlerIds, $joinedDate)
    {
        foreach ($wrestlerIds as $wrestlerId) {
            $this->wrestlers()->attach($wrestlerId, ['joined_at' => $joinedDate]);
        }
    }

    public function addTagTeams($tagTeamIds, $joinedDate)
    {
        foreach ($tagTeamIds as $tagTeamId) {
            $this->tagTeams()->attach($tagTeamId, ['joined_at' => $joinedDate]);
        }
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
     * Undocumented function.
     *
     * @return void
     */
    public function disassemble()
    {
        $this->currentWrestlers()->detach();
        $this->currentTagTeams()->detach();
        $this->updateStatusAndSave();
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
     * Retire a stable and its members.
     *
     * @param  string|null $retiredAt
     * @return void
     */
    public function retire($retiredAt = null)
    {
        throw_unless($this->canBeRetired(), new CannotBeRetiredException('Entity cannot be unretired. This entity is not retired.'));

        $retiredDate = $retiredAt ?: now();

        $this->currentActivation()->update(['ended_at' => $retiredDate]);
        $this->retirements()->create(['started_at' => now()]);
        $this->currentWrestlers->each->retire($retiredDate);
        $this->currentTagTeams->each->retire();
        $this->updateStatusAndSave();
    }

    /**
     * Unretire a stable.
     *
     * @param  string|null $unretiredAt
     * @return $this
     */
    public function unretire($unretiredAt = null)
    {
        throw_unless($this->canBeUnretired(), new CannotBeUnretiredException('Entity cannot be unretired. This entity is not retired.'));

        $unretiredDate = $unretiredAt ?: now();

        $this->currentRetirement()->update(['ended_at' => $unretiredDate]);
        $this->activate($unretiredDate);
        $this->updateStatusAndSave();
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
     * Get the current retirement of the stable.
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
     * Scope a query to only include retired stables.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRetired($query)
    {
        return $this->whereHas('currentRetirement');
    }

    /**
     * Scope a query to only include.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithCurrentRetiredAtDate($query)
    {
        return $query->addSelect(['current_retired_at' => Retirement::select('started_at')
            ->whereColumn('retiree_id', $this->getTable().'.id')
            ->where('retiree_type', $this->getMorphClass())
            ->oldest('started_at')
            ->limit(1),
        ])->withCasts(['current_retired_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the models current retirement date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByCurrentRetiredAtDate($query, $direction = 'asc')
    {
        return $query->orderByRaw("DATE(current_retired_at) $direction");
    }

    /**
     * Determine if the tag team can be retired.
     *
     * @return bool
     */
    public function canBeRetired()
    {
        if ($this->isUnactivated() || $this->hasFutureActivation()) {
            // throw new CannotBeRetiredException('Stable cannot be retired. This Stable does not have an active activation.');
            return false;
        }

        if ($this->isRetired()) {
            // throw new CannotBeRetiredException('Stable cannot be retired. This Stable is retired.');
            return false;
        }

        return true;
    }

    /**
     * Determine if the stable can be unretired.
     *
     * @return bool
     */
    public function canBeUnretired()
    {
        if (! $this->isRetired()) {
            // throw new CannotBeUnretiredException('Entity cannot be unretired. This entity is not retired.');
            return false;
        }

        return true;
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
}
