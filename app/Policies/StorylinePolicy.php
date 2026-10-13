<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Promotions\MembershipRole;
use App\Models\Promotions\Promotion;
use App\Models\Storylines\Storyline;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;

class StorylinePolicy
{
    public function __construct(private readonly PromotionContextService $context) {}

    public function viewAny(User $user): bool
    {
        $promotion = $this->context->current();

        return $promotion instanceof Promotion && $this->context->isEnforced()
            && in_array($this->context->membershipRole($user, $promotion), [MembershipRole::Owner, MembershipRole::Manager], true);
    }

    public function view(User $user, Storyline $storyline): bool
    {
        return $this->context->owns($storyline) && $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Storyline $storyline): bool
    {
        return $this->view($user, $storyline);
    }
}
