<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tournaments\Tournament;
use App\Models\Users\User;

/** PromotionGate grants existing content abilities; no-context policy access fails closed. */
class TournamentPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function view(User $user, Tournament $tournament): bool
    {
        return false;
    }

    public function update(User $user, Tournament $tournament): bool
    {
        return false;
    }
}
