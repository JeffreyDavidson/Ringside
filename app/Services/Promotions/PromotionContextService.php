<?php

declare(strict_types=1);

namespace App\Services\Promotions;

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionMembership;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class PromotionContextService
{
    private ?Promotion $promotion = null;

    private bool $enforced = false;

    /** @var array<string, ?MembershipRole> Active membership roles memoised per "user:promotion" for this request. */
    private array $roles = [];

    /** @var array<int, Collection<int, Promotion>> Active promotions memoised per user id for this request. */
    private array $activePromotions = [];

    /**
     * Select the current promotion. When it was loaded through a user's membership relation its
     * active pivot row seeds the role memo, so authorization needs no further membership query.
     */
    public function set(Promotion $promotion): void
    {
        $this->promotion = $promotion;
        $this->roles = [];

        $pivot = $promotion->relationLoaded('pivot') ? $promotion->getRelation('pivot') : null;

        if ($pivot instanceof PromotionMembership && $pivot->status === MembershipStatus::Active) {
            $this->roles[$this->roleKey($pivot->user_id, $promotion->id)] = $pivot->role;
        }
    }

    /** Reset the whole context, including the membership memo; EstablishPromotionContext calls it as each request starts. */
    public function clear(): void
    {
        $this->promotion = null;
        $this->enforced = false;
        $this->forgetMemberships();
    }

    /** Drop everything memoised from membership rows; call whenever a membership changes. */
    public function forgetMemberships(): void
    {
        $this->roles = [];
        $this->activePromotions = [];
    }

    /** The user's role from an active membership of the promotion, or null when they have none. */
    public function membershipRole(User $user, Promotion $promotion): ?MembershipRole
    {
        $key = $this->roleKey($user->id, $promotion->id);

        if (! array_key_exists($key, $this->roles)) {
            $this->roles[$key] = $promotion->memberships()
                ->forUser($user)
                ->active()
                ->first()
                ?->role;
        }

        return $this->roles[$key];
    }

    /**
     * The user's active promotions ordered by id, loaded once per request.
     *
     * @return Collection<int, Promotion>
     */
    public function activePromotionsFor(User $user): Collection
    {
        return $this->activePromotions[$user->id] ??= $user->promotions()
            ->wherePivot('status', MembershipStatus::Active)
            ->orderBy('promotions.id')
            ->get();
    }

    private function roleKey(int $userId, int $promotionId): string
    {
        return "{$userId}:{$promotionId}";
    }

    public function current(): ?Promotion
    {
        return $this->promotion;
    }

    public function required(): Promotion
    {
        if (! $this->promotion instanceof Promotion) {
            throw new \LogicException('No active promotion context has been established.');
        }

        return $this->promotion;
    }

    public function enforce(): void
    {
        $this->enforced = true;
    }

    public function isEnforced(): bool
    {
        return $this->enforced;
    }

    /**
     * Promotion-scoped queries must match nothing for an authenticated non-administrator
     * whose request has no enforced promotion context (a route outside `promotion.context`).
     * Administrators stay global, and console, queue and guest contexts have no user so they
     * remain unscoped.
     */
    public function failsClosed(): bool
    {
        if ($this->enforced) {
            return false;
        }

        $user = auth()->user();

        return $user instanceof User && ! $user->role->isAdministrator();
    }

    public function owns(Model $model): bool
    {
        if (! $this->enforced || ! $this->promotion instanceof Promotion) {
            return false;
        }

        $promotionKey = $this->promotion->getKey();

        if ($model instanceof EventMatch) {
            $event = $model->relationLoaded('event')
                ? $model->getRelation('event')
                : $model->event()
                    ->withTrashed()
                    ->withoutGlobalScope('promotion_context')
                    ->first();

            return $event instanceof Event && $event->promotion_id === $promotionKey;
        }

        $modelPromotionId = $model->getAttribute('promotion_id');

        return is_int($promotionKey) && $modelPromotionId === $promotionKey;
    }
}
