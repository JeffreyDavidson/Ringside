<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Promotions\MembershipRole;
use App\Models\Concerns\BelongsToPromotion;
use App\Models\Crew\CrewContact;
use App\Models\Events\EventSponsorCommitment;
use App\Models\Events\EventSponsorCommitmentRevision;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Sponsors\Sponsor;
use App\Models\Storylines\Storyline;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Database\Eloquent\Model;

/**
 * Global Gate::before authorization for promotions: the application's permission engine.
 *
 * A user's membership role in the current promotion decides every ability on promotion-owned
 * models at once (see docs/decisions/0001-promotion-gate-is-the-permission-engine.md). The
 * model policies are stubs for the abilities decided here. Returning null falls through to the
 * policies; any boolean is final.
 */
class PromotionGate
{
    /**
     * Subjects that are closed to members entirely, even for reading, mapped to the abilities owners
     * and managers may use on them. A null list means every ability their role allows.
     *
     * @var array<class-string<Model>, list<string>|null>
     */
    private const array MANAGER_ONLY_SUBJECTS = [
        Sponsor::class => null,
        EventSponsorCommitment::class => null,
        EventSponsorCommitmentRevision::class => null,
        CrewContact::class => ['view', 'viewAny', 'create', 'update', 'archive'],
    ];

    /**
     * Subjects an administrator may only create while a promotion is current, because the record
     * has to belong to one.
     *
     * @var list<class-string<Model>>
     */
    private const array PROMOTION_BOUND_CREATES = [CrewContact::class];

    public function __construct(private readonly PromotionContextService $context) {}

    /** @param  array<int, mixed>  $arguments */
    public function before(User $user, string $ability, array $arguments): ?bool
    {
        $subject = $arguments[0] ?? null;

        if ($user->role->isAdministrator()) {
            return $this->authorizeAdministrator($ability, $subject);
        }

        if ($subject instanceof Promotion) {
            return $this->authorizePromotionSubject($user, $ability, $subject);
        }

        // Creative planning has a stricter read boundary than ordinary promotion content.
        if ($subject instanceof Storyline || $subject === Storyline::class) {
            return null;
        }

        return $this->authorizePromotionOwnedSubject($user, $ability, $subject);
    }

    /** Administrators may do anything except touch another promotion's models while a promotion is enforced. */
    private function authorizeAdministrator(string $ability, mixed $subject): bool
    {
        if ($ability === 'create' && in_array($subject, self::PROMOTION_BOUND_CREATES, true) && ! $this->context->current() instanceof Promotion) {
            return false;
        }

        $isForeignPromotionModel = $subject instanceof Model
            && $this->isPromotionOwned($subject)
            && $this->context->isEnforced()
            && ! $this->context->owns($subject);

        return ! $isForeignPromotionModel;
    }

    /** Members act on the promotion itself according to their active membership. */
    private function authorizePromotionSubject(User $user, string $ability, Promotion $promotion): bool
    {
        $role = $this->context->membershipRole($user, $promotion);

        if (! $role instanceof MembershipRole) {
            return false;
        }

        return match ($ability) {
            'view' => true,
            'manageSponsors' => $role === MembershipRole::Owner || $role === MembershipRole::Manager,
            'manageMembers', 'update' => $role === MembershipRole::Owner,
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

        $role = $this->context->membershipRole($user, $promotion);
        $class = $subject instanceof Model ? $subject::class : $subject;

        if (is_string($class) && array_key_exists($class, self::MANAGER_ONLY_SUBJECTS)) {
            return $this->allowsManagerOnlySubject($role, $class, $ability);
        }

        return $role?->allows($ability) ?? false;
    }

    /** @param  class-string<Model>  $class */
    private function allowsManagerOnlySubject(?MembershipRole $role, string $class, string $ability): bool
    {
        if (! in_array($role, [MembershipRole::Owner, MembershipRole::Manager], true)) {
            return false;
        }

        $abilities = self::MANAGER_ONLY_SUBJECTS[$class];

        return $abilities === null
            ? $role->allows($ability)
            : in_array($ability, $abilities, true);
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
}
