<?php

namespace App\Models;

use App\Eloquent\Concerns\HasCustomRelationships;
use App\Enums\StableStatus;
use App\Traits\HasCachedAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stable extends Model
{
    use SoftDeletes,
        HasCachedAttributes,
        HasCustomRelationships,
        Concerns\CanBeRetired,
        Concerns\CanBeActivated,
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


    public function disassemble()
    {
        $this->currentWrestlers()->detach();
        $this->currentTagteams()->detach();
        $this->touch();

        return $this;
    }

    /**
     * Get the wrestlers belonging to the tag team.
     *
     * @return App\Eloquent\Relationships\LeaveableBelongsToMany
     */
    public function wrestlerHistory()
    {
        return $this->leaveableBelongsToMany(Wrestler::class, 'tag_team_wrestler', 'tag_team_id', 'wrestler_id');
    }

    /**
     * Get all current wrestlers that are members of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function currentWrestlers()
    {
        return $this->wrestlerHistory()->current();
    }

    /**
     * Get all current wrestlers that are members of the tag team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphByMany
     */
    public function previousWrestlers()
    {
        return $this->wrestlerHistory()->detached();
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

        $this->currentWrestlers->each->retire();

        $this->currentTagTeams->each->retire();

        return $this->touch();
    }
}
