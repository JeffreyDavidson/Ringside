<?php

declare(strict_types=1);

namespace App\Models\Promotions;

use App\Builders\Promotions\PromotionMembershipBuilder;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $promotion_id
 * @property int $user_id
 * @property MembershipRole $role
 * @property MembershipStatus $status
 *
 * @method static PromotionMembershipBuilder<static>|PromotionMembership newModelQuery()
 * @method static PromotionMembershipBuilder<static>|PromotionMembership newQuery()
 * @method static PromotionMembershipBuilder<static>|PromotionMembership query()
 */
#[Fillable('promotion_id', 'user_id', 'role', 'status')]
#[Table(name: 'promotion_user')]
#[UseEloquentBuilder(PromotionMembershipBuilder::class)]
class PromotionMembership extends Pivot
{
    /** @return BelongsTo<Promotion, $this> */
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> */
    #[\Override]
    protected function casts(): array
    {
        return [
            'role' => MembershipRole::class,
            'status' => MembershipStatus::class,
        ];
    }
}
