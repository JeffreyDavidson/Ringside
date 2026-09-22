<?php

declare(strict_types=1);

namespace App\Models\Promotions;

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Users\User;
use Database\Factories\Promotions\PromotionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, PromotionMembership> $memberships
 */
#[Fillable('name', 'slug')]
#[UseFactory(PromotionFactory::class)]
class Promotion extends Model
{
    /** @use HasFactory<PromotionFactory> */
    use HasFactory;

    /** @return BelongsToMany<User, $this, PromotionMembership> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(PromotionMembership::class)
            ->withPivot(['role', 'status'])
            ->withTimestamps();
    }

    /** @return HasMany<PromotionMembership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(PromotionMembership::class);
    }

    public function hasActiveMember(User $user): bool
    {
        return $this->users()
            ->whereKey($user)
            ->wherePivot('status', MembershipStatus::Active)
            ->exists();
    }

    public function hasMemberWithRole(User $user, MembershipRole ...$roles): bool
    {
        return $this->users()
            ->whereKey($user)
            ->wherePivotIn('role', $roles)
            ->wherePivot('status', MembershipStatus::Active)
            ->exists();
    }
}
