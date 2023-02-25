<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface Manageables
{
    /**
     * Get all of the wrestlers that have been managed by model.
     */
    public function wrestlers(): MorphToMany;

    /**
     * Get the current wrestlers that is managed by model.
     */
    public function currentWrestlers(): MorphToMany;

    /**
     * Get all previous wrestlers that have been managed by model.
     */
    public function previousWrestlers(): MorphToMany;

    /**
     * Get all of the tag teams that have been managed by model.
     */
    public function tagTeams(): MorphToMany;

    /**
     * Get the current tag teams that is managed by model.
     */
    public function currentTagTeams(): MorphToMany;

    /**
     * Get all previous tag teams that have been managed by model.
     */
    public function previousTagTeams(): MorphToMany;
}
