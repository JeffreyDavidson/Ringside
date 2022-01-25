<?php

namespace App\Models\Contracts;

interface Manageables
{
    /**
     * Get all of the wrestlers that have been managed by model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function wrestlers();

    /**
     * Get the current wrestlers that is managed by model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function currentWrestlers();

    /**
     * Get all previous wrestlers that have been managed by model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function previousWrestlers();

    /**
     * Get all of the tag teams that have been managed by model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function tagTeams();

    /**
     * Get the current tag teams that is managed by model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function currentTagTeams();

    /**
     * Get all previous tag teams that have been managed by model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function previousTagTeams();
}
