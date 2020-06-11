<?php

namespace App\Models\Concerns;

use App\Models\Member;

class MemberHistory
{
    /** string $modelClassName */
    private $modelClassName;
​
    /**
     * Undocumented function
     *
     * @param string $modelClassName
     */
    public function __construct(string $modelClassName)
    {
        $this->modelClassName = $modelClassName;
    }
​
    /**
     * Undocumented function
     *
     * @return void
     */
    public function history()
    {
        return $this->leaveableMorphedByMany($this->modelClassName, 'member')
                    ->using(Member::class);
    }
​
    /**
     * Undocumented function
     *
     * @return void
     */
    public function current()
    {
        return $this->history()->current();
    }
​
    /**
     * Undocumented function
     *
     * @return void
     */
    public function previous()
    {
        return $this->history()->previous();
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function addMembers($memberIds)
    {
        return $this->history()->sync($memberIds);

        return $this;
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function getCurrentMembersAttribute()
    {
        if (!$this->relationLoaded('currentMembers')) {
            $this->setRelation('currentMembers',
                $tagTeams = $this->currentTagTeams()->get(),
                $wrestlers = $this->currentWrestlers()->get(),
                collect([$tagTeams, $wrestlers])
            );
        }

        return $this->getRelation('currentMembers')->first();
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function getPreviousMembersAttribute()
    {
        if (!$this->relationLoaded('previousTagTeam')) {
            $this->setRelation('previousTagTeam', $this->previousTagTeam()->get());
        }

        return $this->getRelation('previousTagTeam')->first();
    }
}
