<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface Manageables
{
    /**
     * Get all the wrestlers that have been managed by model.
     *
     * @return MorphToMany<Wrestler>
     */
    public function wrestlers(): MorphToMany;

    /**
     * Get the current wrestlers that is managed by model.
     *
     * @return MorphToMany<Wrestler>
     */
    public function currentWrestlers(): MorphToMany;

    /**
     * Get all previous wrestlers that have been managed by model.
     *
     * @return MorphToMany<Wrestler>
     */
    public function previousWrestlers(): MorphToMany;

    /**
     * Get all the tag teams that have been managed by model.
     *
     * @return MorphToMany<TagTeam>
     */
    public function tagTeams(): MorphToMany;

    /**
     * Get the current tag teams that is managed by model.
     *
     * @return MorphToMany<TagTeam>
     */
    public function currentTagTeams(): MorphToMany;

    /**
     * Get all previous tag teams that have been managed by model.
     *
     * @return MorphToMany<TagTeam>
     */
    public function previousTagTeams(): MorphToMany;
}
