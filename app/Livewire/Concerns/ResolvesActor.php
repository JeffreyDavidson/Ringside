<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\Users\User;

/** The signed-in user a component hands to an Action as the actor; Actions never read the session themselves. */
trait ResolvesActor
{
    protected function actor(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
