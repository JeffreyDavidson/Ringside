<?php

declare(strict_types=1);

namespace App\Builders\Promotions;

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\PromotionMembership;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of PromotionMembership
 *
 * @extends Builder<TModel>
 */
class PromotionMembershipBuilder extends Builder
{
    public function forUser(User $user): static
    {
        return $this->whereBelongsTo($user, 'user');
    }

    public function active(): static
    {
        return $this->where('status', MembershipStatus::Active->value);
    }

    public function withRole(MembershipRole ...$roles): static
    {
        $this->whereIn('role', array_map(
            fn (MembershipRole $role): string => $role->value,
            $roles,
        ));

        return $this;
    }
}
