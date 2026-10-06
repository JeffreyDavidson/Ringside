<?php

declare(strict_types=1);

namespace App\Models\Promotions;

use App\Enums\Promotions\MembershipRole;
use App\Models\Users\User;
use Database\Factories\Promotions\PromotionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;

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

    /** Show a stored (UTC) instant as wall-clock time in the promotion's time zone; no promotion means the application time zone. */
    public static function toLocalTime(?self $promotion, Carbon $date): Carbon
    {
        return $date->copy()->setTimezone(self::zoneOf($promotion));
    }

    /** Read a wall-clock time entered in the promotion's time zone (a datetime-local value) as the UTC instant to store. */
    public static function parseLocalTime(?self $promotion, string $value): Carbon
    {
        return Date::parse($value, self::zoneOf($promotion))->utc();
    }

    /** Whether a wall-clock time exists in the promotion's zone; the hour skipped when clocks move forward does not. */
    public static function localTimeExists(?self $promotion, string $value): bool
    {
        $format = 'Y-m-d H:i:s';

        return Date::parse($value, self::zoneOf($promotion))->format($format) === Date::parse($value, 'UTC')->format($format);
    }

    public static function zoneOf(?self $promotion): string
    {
        return $promotion instanceof self ? $promotion->timezone : config()->string('app.timezone');
    }

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

    public function hasActiveMember(User $user): bool
    {
        return $this->memberships()
            ->forUser($user)
            ->active()
            ->exists();
    }

    public function hasMemberWithRole(User $user, MembershipRole ...$roles): bool
    {
        return $this->memberships()
            ->forUser($user)
            ->active()
            ->withRole(...$roles)
            ->exists();
    }
}
