<?php

declare(strict_types=1);

namespace App\Models\Promotions;

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
 * @property string $timezone
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, PromotionMembership> $memberships
 * @property-read Collection<int, PromotionInvitation> $invitations
 */
#[Fillable('name', 'slug', 'timezone')]
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

    /** @return HasMany<PromotionInvitation, $this> */
    public function invitations(): HasMany
    {
        return $this->hasMany(PromotionInvitation::class);
    }
}
