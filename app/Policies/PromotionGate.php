<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Promotions\MembershipRole;
use App\Models\Concerns\BelongsToPromotion;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionMembership;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Database\Eloquent\Model;

/**
 * Global Gate::before authorization for promotions.
 *
 * Returning null falls through to the model policies; any boolean is final.
 */
class PromotionGate
{
    public function __construct(private readonly PromotionContextService $context) {}

    /** @param  array<int, mixed>  $arguments */
    public function before(User $user, string $ability, array $arguments): ?bool
    {
        $subject = $arguments[0] ?? null;

        if ($user->role->isAdministrator()) {
            return $this->authorizeAdministrator($subject);
        }

        if ($subject instanceof Promotion) {
            return $this->authorizePromotionSubject($user, $ability, $subject);
        }

        return $this->authorizePromotionOwnedSubject($user, $ability, $subject);
    }

    /** Administrators may do anything except touch another promotion's models while a promotion is enforced. */
    private function authorizeAdministrator(mixed $subject): bool
    {
        $isForeignPromotionModel = $subject instanceof Model
            && $this->isPromotionOwned($subject)
            && $this->context->isEnforced()
            && ! $this->context->owns($subject);

        return ! $isForeignPromotionModel;
    }

    /** Members act on the promotion itself according to their active membership. */
    private function authorizePromotionSubject(User $user, string $ability, Promotion $promotion): bool
    {
        $membership = $this->activeMembership($user, $promotion);

        if (! $membership instanceof PromotionMembership) {
            return false;
        }

        return match ($ability) {
            'view' => true,
            'manageMembers', 'update' => $membership->role === MembershipRole::Owner,
            default => false,
        };
    }

    /** Promotion-owned models and class strings are authorized by the current promotion's membership role. */
    private function authorizePromotionOwnedSubject(User $user, string $ability, mixed $subject): ?bool
    {
        if (! $this->isPromotionOwned($subject)) {
            return null;
        }

        if (! $this->context->isEnforced()) {
            return null;
        }

        $promotion = $this->context->current();

        if (! $promotion instanceof Promotion) {
            return false;
        }

        if ($subject instanceof Model && ! $this->context->owns($subject)) {
            return false;
        }

        return $this->activeMembership($user, $promotion)?->role->allows($ability) ?? false;
    }

    /** Determine whether the model or class string belongs to a promotion. */
    private function isPromotionOwned(mixed $subject): bool
    {
        if (! $subject instanceof Model && (! is_string($subject) || ! class_exists($subject))) {
            return false;
        }

        return $subject instanceof EventMatch
            || $subject === EventMatch::class
            || in_array(BelongsToPromotion::class, class_uses_recursive($subject), true);
    }

    private function activeMembership(User $user, Promotion $promotion): ?PromotionMembership
    {
        return $promotion->memberships()
            ->forUser($user)
            ->active()
            ->first();
    }
}
