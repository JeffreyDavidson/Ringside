<?php

namespace App\Models;

use App\Eloquent\Concerns\HasCustomRelationships;
use App\Enums\TagTeamStatus;
use App\Traits\HasCachedAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TagTeam extends Model
{
    use SoftDeletes,
        HasCachedAttributes,
        HasCustomRelationships,
        Concerns\CanBeRetired,
        Concerns\CanBeSuspended,
        Concerns\CanBeEmployed,
        Concerns\CanBeBooked,
        Concerns\Unguarded;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => TagTeamStatus::class,
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
     * Get the wrestlers belonging to the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function wrestlerHistory()
    {
        return $this->belongsToMany(Wrestler::class, 'tag_team_wrestler', 'tag_team_id', 'wrestler_id')->withTimestamps();
    }

    /**
     * Get all current wrestlers that are members of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function currentWrestlers()
    {
        return $this->wrestlerHistory()
                    ->whereNull('left_at')
                    ->limit(2);
    }

    /**
     * Get all current wrestlers that are members of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function previousWrestlers()
    {
        return $this->wrestlerHistory()
                    ->whereNotNull('left_at');
    }

    /**
     * Get the stables the tag team are members of.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function stableHistory()
    {
        return $this->leaveableMorphToMany(Stable::class, 'member');
    }

    /**
     * Get the current stable of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function currentStable()
    {
        return $this->stableHistory()->current();
    }

    /**
     * Get the current stable of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function previousStables()
    {
        return $this->stableHistory()->detached();
    }

    /**
     * Get the combined weight of both wrestlers in a tag team.
     *
     * @return int
     */
    public function getCombinedWeightAttribute()
    {
        return $this->currentWrestlers->sum('weight');
    }

    /**
     * Add multiple wrestlers to a tag team.
     *
     * @param  array  $wrestlers
     * @param  string|null $dateJoined
     * @return $this
     */
    public function addWrestlers($wrestlerIds, $dateJoined = null)
    {
        $this->wrestlerHistory()->sync([
            $wrestlerIds[0] => ['joined_at' => $dateJoined],
            $wrestlerIds[1] => ['joined_at' => $dateJoined]
        ]);

        return $this;
    }

    /**
     * Determine if the model can be reinstated.
     *
     * @return bool
     */
    public function canBeEmployed()
    {
        if ($this->isCurrentlyEmployed()) {
            return false;
        }

        if ($this->currentWrestlers->count() != 2) {
            return false;
        }

        return true;
    }

    /**
     * Employ a tag team.
     *
     * @return bool
     */
    public function employ($startAtDate = null)
    {
        $startAtDate = $startAtDate ?? now();
        $this->employments()->updateOrCreate(['ended_at' => null], ['started_at' => $startAtDate]);
        $this->wrestlerHistory->each->employ($startAtDate);

        return $this->touch();
    }

    /**
     * Determine if the model can be retired.
     *
     * @return bool
     */
    public function canBeRetired()
    {
        if (! $this->isCurrentlyEmployed()) {
            return false;
        }

        if ($this->isRetired()) {
            return false;
        }

        return true;
    }

    /**
     * Retire a tag team.
     *
     * @return \App\Models\Retirement
     */
    public function retire($retiredAt = null)
    {
        $retiredDate = $retiredAt ?: now();

        if ($this->is_suspended) {
            $this->reinstate($retiredAt);
            $this->currentWrestlers->each->reinstate($retiredAt);
        }

        $this->currentEmployment()->update(['ended_at' => $retiredDate]);
        $this->currentWrestlers()->each(function ($wrestler) use ($retiredDate) {
            $wrestler->currentEmployment()->update(['ended_at' => $retiredDate]);
        });

        $this->retirements()->create(['started_at' => $retiredDate]);
        $this->currentWrestlers->each->retire($retiredDate);

        return $this->touch();
    }

    /**
     * Unretire a tag team.
     *
     * @return bool
     */
    public function unretire()
    {
        $dateRetired = $this->currentRetirement->started_at;

        $this->currentRetirement()->update(['ended_at' => now()]);

        $this->wrestlerHistory()
            ->whereHas('currentRetirement', function ($query) use ($dateRetired) {
                $query->whereDate('started_at', $dateRetired);
            })
            ->get()
            ->each
            ->unretire();

        return $this->touch();
    }

    /**
     * Determine if the model can be reinstated.
     *
     * @return bool
     */
    public function canBeSuspended()
    {
        if (! $this->isCurrentlyEmployed()) {
            return false;
        }

        if ($this->isSuspended()) {
            return false;
        }

        return true;
    }

    /**
     * Suspend a tag team.
     *
     * @return \App\Models\Suspension
     */
    public function suspend($suspendedAt = null)
    {
        $suspendedDate = $suspendedAt ?: now();

        $this->suspensions()->create(['started_at' => $suspendedDate]);
        $this->currentWrestlers->each->suspend($suspendedDate);

        return $this->touch();
    }

    /**
     * Determine if the model can be reinstated.
     *
     * @return bool
     */
    public function canBeReinstated()
    {
        if (! $this->isCurrentlyEmployed()) {
            return false;
        }

        if (! $this->isSuspended()) {
            return false;
        }

        return true;
    }

    /**
     * Reinstate a tag team.
     *
     * @return bool
     */
    public function reinstate($reinstatedAt = null)
    {
        $reinstatedDate = $reinstatedAt ?: now();

        $this->currentSuspension()->update(['ended_at' => $reinstatedDate]);
        $this->currentWrestlers->each->reinstate($reinstatedDate);

        return $this->touch();
    }

    /**
     * @return bool
     */
    public function isBookable()
    {
        if ($this->currentEmployment()->doesntExist()) {
            return false;
        }

        if ($this->currentSuspension()->exists()) {
            return false;
        }

        if ($this->currentRetirement()->exists()) {
            return false;
        }

        if (! $this->currentWrestlers->each->isBookable()) {
            return false;
        }

        return true;
    }
}
