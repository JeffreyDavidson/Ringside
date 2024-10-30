<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface Manageables
{
    public function wrestlers(): MorphToMany;

    public function currentWrestlers(): MorphToMany;

    public function previousWrestlers(): MorphToMany;

    public function tagTeams(): MorphToMany;

    public function currentTagTeams(): MorphToMany;

    public function previousTagTeams(): MorphToMany;
}
