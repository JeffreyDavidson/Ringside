<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface Manageables
{
    public function wrestlers(): BelongsToMany;

    public function currentWrestlers(): BelongsToMany;

    public function previousWrestlers(): BelongsToMany;

    public function tagTeams(): BelongsToMany;

    public function currentTagTeams(): BelongsToMany;

    public function previousTagTeams(): BelongsToMany;
}
