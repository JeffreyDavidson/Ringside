<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 */
interface DefinesManagedAliases
{
    /**
     * Get all wrestlers this model manages.
     *
     * @return BelongsToMany<Wrestler, TDeclaringModel>
     */
    public function wrestlers(): BelongsToMany;

    /**
     * Get wrestlers currently managed by this model.
     *
     * These are wrestlers who were hired but not yet released.
     *
     * @return BelongsToMany<Wrestler, TDeclaringModel>
     */
    public function currentWrestlers(): BelongsToMany;

    /**
     * Get wrestlers previously managed by this model.
     *
     * These are wrestlers who have already left or been released.
     *
     * @return BelongsToMany<Wrestler, TDeclaringModel>
     */
    public function previousWrestlers(): BelongsToMany;

    /**
     * Get all tag teams this model manages.
     *
     * @return BelongsToMany<TagTeam, TDeclaringModel>
     */
    public function tagTeams(): BelongsToMany;

    /**
     * Get tag teams currently managed by this model.
     *
     * These are tag teams with an active management relationship.
     *
     * @return BelongsToMany<TagTeam, TDeclaringModel>
     */
    public function currentTagTeams(): BelongsToMany;

    /**
     * Get tag teams previously managed by this model.
     *
     * These are tag teams no longer managed.
     *
     * @return BelongsToMany<TagTeam, TDeclaringModel>
     */
    public function previousTagTeams(): BelongsToMany;
}
