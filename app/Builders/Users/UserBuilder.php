<?php

declare(strict_types=1);

namespace App\Builders\Users;

use App\Builders\Concerns\HasNameSearch;
use App\Enums\Users\UserStatus;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of User
 *
 * @extends Builder<TModel>
 */
class UserBuilder extends Builder
{
    use HasNameSearch;

    public function memberOfPromotion(Promotion $promotion): static
    {
        return $this->whereHas('promotionMemberships', function (Builder $query) use ($promotion): void {
            $query->where('promotion_id', $promotion->getKey());
        });
    }

    public function whereStatus(UserStatus $status): static
    {
        return $this->where('status', $status->value);
    }
}
