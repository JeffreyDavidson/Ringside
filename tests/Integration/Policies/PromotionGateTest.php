<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Facades\Gate;

/**
 * Characterization of the global Gate::before promotion authorization.
 *
 * `raw('unmapped-ability', ...)` returns `null` only when the hook falls through to the
 * policies, because no policy defines that ability. Any boolean means the hook decided.
 */
const PROMOTION_GATE_FALL_THROUGH_ABILITY = 'unmapped-ability';

/**
 * Establish the requested promotion context state.
 */
function promotionGateContext(string $state, Promotion $own): void
{
    $context = app(PromotionContextService::class);

    match ($state) {
        'unset' => null,
        'set only' => $context->set($own),
        'enforced' => (function () use ($context, $own): void {
            $context->set($own);
            $context->enforce();
        })(),
        'enforced without promotion' => $context->enforce(),
        default => throw new InvalidArgumentException("Unknown context state [{$state}]."),
    };
}

/**
 * Create a user holding the given membership of the promotion, or no membership at all.
 */
function promotionGateUser(string $membership, Promotion $own, Promotion $foreign): User
{
    if ($membership === 'administrator') {
        return administrator();
    }

    $user = basicUser();

    [$promotion, $role, $status] = match ($membership) {
        'none' => [null, null, null],
        'foreign owner' => [$foreign, MembershipRole::Owner, MembershipStatus::Active],
        'member' => [$own, MembershipRole::Member, MembershipStatus::Active],
        'manager' => [$own, MembershipRole::Manager, MembershipStatus::Active],
        'owner' => [$own, MembershipRole::Owner, MembershipStatus::Active],
        'suspended member' => [$own, MembershipRole::Member, MembershipStatus::Suspended],
        'suspended owner' => [$own, MembershipRole::Owner, MembershipStatus::Suspended],
        default => throw new InvalidArgumentException("Unknown membership [{$membership}]."),
    };

    $promotion?->users()->attach($user, ['role' => $role, 'status' => $status]);

    return $user;
}

/**
 * Build the arguments handed to the Gate for the named subject.
 *
 * @return list<mixed>
 */
function promotionGateArguments(string $subject, Promotion $own, Promotion $foreign): array
{
    $ownEvent = fn (): Event => Event::factory()->for($own, 'promotion')->create();
    $foreignEvent = fn (): Event => Event::factory()->for($foreign, 'promotion')->create();

    return match ($subject) {
        'none' => [],
        'own wrestler' => [Wrestler::factory()->for($own, 'promotion')->create()],
        'foreign wrestler' => [Wrestler::factory()->for($foreign, 'promotion')->create()],
        'own title' => [Title::factory()->for($own, 'promotion')->create()],
        'foreign title' => [Title::factory()->for($foreign, 'promotion')->create()],
        'own event' => [$ownEvent()],
        'foreign event' => [$foreignEvent()],
        'own event match' => [EventMatch::factory()->forEvent($ownEvent())->create()],
        'foreign event match' => [EventMatch::factory()->forEvent($foreignEvent())->create()],
        'own promotion' => [$own],
        'foreign promotion' => [$foreign],
        'venue' => [Venue::factory()->create()],
        'user' => [User::factory()->create()],
        'wrestler class' => [Wrestler::class],
        'title class' => [Title::class],
        'event class' => [Event::class],
        'event match class' => [EventMatch::class],
        'venue class' => [Venue::class],
        'promotion class' => [Promotion::class],
        'unknown string' => ['not-a-class'],
        'class with foreign wrestler second argument' => [Wrestler::class, Wrestler::factory()->for($foreign, 'promotion')->create()],
        default => throw new InvalidArgumentException("Unknown subject [{$subject}]."),
    };
}

/**
 * Create the two promotions used by every case.
 *
 * @return array{Promotion, Promotion}
 */
function promotionGatePromotions(): array
{
    return [Promotion::factory()->create(), Promotion::factory()->create()];
}

describe('administrators', function () {
    test('are allowed everything while the promotion context is not enforced', function (string $context, string $subject) {
        // Arrange
        [$own, $foreign] = promotionGatePromotions();
        $admin = promotionGateUser('administrator', $own, $foreign);
        $arguments = promotionGateArguments($subject, $own, $foreign);
        promotionGateContext($context, $own);

        // Act
        $result = Gate::forUser($admin)->raw('view', $arguments);

        // Assert
        expect($result)->toBeTrue();
    })->with(['unset', 'set only'])->with([
        'none', 'own wrestler', 'foreign wrestler', 'foreign title', 'own event', 'foreign event',
        'own event match', 'foreign event match', 'own promotion', 'foreign promotion', 'venue', 'user',
        'wrestler class', 'event match class', 'venue class', 'promotion class', 'unknown string',
        'class with foreign wrestler second argument',
    ]);

    test('are allowed or denied by ownership of promotion owned models while the context is enforced', function (string $subject, bool $expected) {
        // Arrange
        [$own, $foreign] = promotionGatePromotions();
        $admin = promotionGateUser('administrator', $own, $foreign);
        $arguments = promotionGateArguments($subject, $own, $foreign);
        promotionGateContext('enforced', $own);

        // Act
        $result = Gate::forUser($admin)->raw('view', $arguments);

        // Assert
        expect($result)->toBe($expected);
    })->with([
        'own wrestler' => ['own wrestler', true],
        'foreign wrestler' => ['foreign wrestler', false],
        'own title' => ['own title', true],
        'foreign title' => ['foreign title', false],
        'own event' => ['own event', true],
        'foreign event' => ['foreign event', false],
        'own event match' => ['own event match', true],
        'foreign event match' => ['foreign event match', false],
    ]);

    test('are allowed everything that is not a promotion owned model while the context is enforced', function (string $subject) {
        // Arrange
        [$own, $foreign] = promotionGatePromotions();
        $admin = promotionGateUser('administrator', $own, $foreign);
        $arguments = promotionGateArguments($subject, $own, $foreign);
        promotionGateContext('enforced', $own);

        // Act
        $result = Gate::forUser($admin)->raw('view', $arguments);

        // Assert
        expect($result)->toBeTrue();
    })->with([
        'none', 'own promotion', 'foreign promotion', 'venue', 'user', 'wrestler class', 'title class',
        'event class', 'event match class', 'venue class', 'promotion class', 'unknown string',
        'class with foreign wrestler second argument',
    ]);

    test('are denied promotion owned models when the context is enforced without a promotion', function (string $subject) {
        // Arrange
        [$own, $foreign] = promotionGatePromotions();
        $admin = promotionGateUser('administrator', $own, $foreign);
        $arguments = promotionGateArguments($subject, $own, $foreign);
        promotionGateContext('enforced without promotion', $own);

        // Act
        $result = Gate::forUser($admin)->raw('view', $arguments);

        // Assert
        expect($result)->toBeFalse();
    })->with(['own wrestler', 'foreign wrestler', 'own event', 'own event match', 'foreign event match']);

    test('are allowed everything else when the context is enforced without a promotion', function (string $subject) {
        // Arrange
        [$own, $foreign] = promotionGatePromotions();
        $admin = promotionGateUser('administrator', $own, $foreign);
        $arguments = promotionGateArguments($subject, $own, $foreign);
        promotionGateContext('enforced without promotion', $own);

        // Act
        $result = Gate::forUser($admin)->raw('view', $arguments);

        // Assert
        expect($result)->toBeTrue();
    })->with(['none', 'own promotion', 'venue', 'user', 'wrestler class', 'event match class']);

    test('are allowed any ability name', function (string $ability) {
        // Arrange
        [$own, $foreign] = promotionGatePromotions();
        $admin = promotionGateUser('administrator', $own, $foreign);
        promotionGateContext('enforced', $own);

        // Act
        $result = Gate::forUser($admin)->raw($ability, [Wrestler::factory()->for($own, 'promotion')->create()]);

        // Assert
        expect($result)->toBeTrue();
    })->with(['view', 'viewAny', 'create', 'delete', 'manageMembers', PROMOTION_GATE_FALL_THROUGH_ABILITY]);
});

describe('non administrators on a promotion subject', function () {
    test('are authorized by their active membership of that promotion regardless of the context', function (string $context, string $membership, array $expected) {
        // Arrange
        [$own, $foreign] = promotionGatePromotions();
        $user = promotionGateUser($membership, $own, $foreign);
        promotionGateContext($context, $own);

        // Act
        $results = collect(array_keys($expected))
            ->mapWithKeys(fn (string $ability): array => [$ability => Gate::forUser($user)->raw($ability, [$own])])
            ->all();

        // Assert
        expect($results)->toBe($expected);
    })->with(['unset', 'set only', 'enforced', 'enforced without promotion'])->with([
        'no membership' => ['none', ['view' => false, 'viewAny' => false, 'update' => false, 'manageMembers' => false, 'delete' => false, 'create' => false]],
        'owner of another promotion' => ['foreign owner', ['view' => false, 'viewAny' => false, 'update' => false, 'manageMembers' => false, 'delete' => false, 'create' => false]],
        'suspended member' => ['suspended member', ['view' => false, 'viewAny' => false, 'update' => false, 'manageMembers' => false, 'delete' => false, 'create' => false]],
        'suspended owner' => ['suspended owner', ['view' => false, 'viewAny' => false, 'update' => false, 'manageMembers' => false, 'delete' => false, 'create' => false]],
        'member' => ['member', ['view' => true, 'viewAny' => false, 'update' => false, 'manageMembers' => false, 'delete' => false, 'create' => false]],
        'manager' => ['manager', ['view' => true, 'viewAny' => false, 'update' => false, 'manageMembers' => false, 'delete' => false, 'create' => false]],
        'owner' => ['owner', ['view' => true, 'viewAny' => false, 'update' => true, 'manageMembers' => true, 'delete' => false, 'create' => false]],
    ]);

    test('are denied a promotion they are not an active member of', function (string $membership) {
        // Arrange
        [$own, $foreign] = promotionGatePromotions();
        $user = promotionGateUser($membership, $own, $foreign);
        promotionGateContext('enforced', $own);

        // Act
        $results = [
            'view' => Gate::forUser($user)->raw('view', [$foreign]),
            'update' => Gate::forUser($user)->raw('update', [$foreign]),
            'manageMembers' => Gate::forUser($user)->raw('manageMembers', [$foreign]),
        ];

        // Assert
        expect($results)->toBe(['view' => false, 'update' => false, 'manageMembers' => false]);
    })->with(['member', 'manager', 'owner']);

    test('receive a decision for any ability name rather than falling through', function () {
        // Arrange
        [$own, $foreign] = promotionGatePromotions();
        $user = promotionGateUser('owner', $own, $foreign);

        // Act
        $result = Gate::forUser($user)->raw(PROMOTION_GATE_FALL_THROUGH_ABILITY, [$own]);

        // Assert
        expect($result)->toBeFalse();
    });
});

describe('non administrators on a promotion owned subject', function () {
    test('fall through to the policies while the context is not enforced', function (string $context, string $membership, string $subject) {
        // Arrange
        [$own, $foreign] = promotionGatePromotions();
        $user = promotionGateUser($membership, $own, $foreign);
        $arguments = promotionGateArguments($subject, $own, $foreign);
        promotionGateContext($context, $own);

        // Act
        $result = Gate::forUser($user)->raw(PROMOTION_GATE_FALL_THROUGH_ABILITY, $arguments);
        $policyOutcome = Gate::forUser($user)->allows('viewAny', $arguments);

        // Assert
        expect($result)->toBeNull()
            ->and($policyOutcome)->toBeFalse();
    })->with(['unset', 'set only'])->with(['none', 'owner'])->with([
        'own wrestler', 'foreign wrestler', 'own event', 'own event match', 'foreign event match',
        'wrestler class', 'title class', 'event class', 'event match class',
    ]);

    test('are denied when the context is enforced without a promotion', function (string $membership, string $subject) {
        // Arrange
        [$own, $foreign] = promotionGatePromotions();
        $user = promotionGateUser($membership, $own, $foreign);
        $arguments = promotionGateArguments($subject, $own, $foreign);
        promotionGateContext('enforced without promotion', $own);

        // Act
        $result = Gate::forUser($user)->raw('view', $arguments);

        // Assert
        expect($result)->toBeFalse();
    })->with(['none', 'owner'])->with([
        'own wrestler', 'own event match', 'wrestler class', 'event match class',
    ]);

    test('are denied models owned by another promotion even as an owner', function (string $subject, string $ability) {
        // Arrange
        [$own, $foreign] = promotionGatePromotions();
        $user = promotionGateUser('owner', $own, $foreign);
        $arguments = promotionGateArguments($subject, $own, $foreign);
        promotionGateContext('enforced', $own);

        // Act
        $result = Gate::forUser($user)->raw($ability, $arguments);

        // Assert
        expect($result)->toBeFalse();
    })->with([
        'foreign wrestler', 'foreign title', 'foreign event', 'foreign event match',
    ])->with(['view', 'update', 'delete']);

    test('only inspect the first argument, so a foreign model after a class string is not checked', function (string $ability) {
        // Arrange
        [$own, $foreign] = promotionGatePromotions();
        $user = promotionGateUser('owner', $own, $foreign);
        $arguments = promotionGateArguments('class with foreign wrestler second argument', $own, $foreign);
        promotionGateContext('enforced', $own);

        // Act
        $result = Gate::forUser($user)->raw($ability, $arguments);

        // Assert
        expect($result)->toBeTrue();
    })->with(['view', 'update', 'delete']);

    test('are authorized by the role of their active membership for own models and class strings', function (string $membership, array $allowed, string $subject) {
        // Arrange
        [$own, $foreign] = promotionGatePromotions();
        $user = promotionGateUser($membership, $own, $foreign);
        $arguments = promotionGateArguments($subject, $own, $foreign);
        promotionGateContext('enforced', $own);
        $abilities = ['view', 'viewAny', 'create', 'update', 'delete', 'restore', 'employ', 'debut', 'manageMembers', PROMOTION_GATE_FALL_THROUGH_ABILITY];

        // Act
        $results = collect($abilities)
            ->mapWithKeys(fn (string $ability): array => [$ability => Gate::forUser($user)->raw($ability, $arguments)])
            ->all();

        // Assert
        expect($results)->toBe(collect($abilities)
            ->mapWithKeys(fn (string $ability): array => [$ability => in_array($ability, $allowed, true)])
            ->all());
    })->with([
        'no membership' => ['none', []],
        'owner of another promotion' => ['foreign owner', []],
        'suspended member' => ['suspended member', []],
        'suspended owner' => ['suspended owner', []],
        'member' => ['member', ['view', 'viewAny']],
        'manager' => ['manager', ['view', 'viewAny', 'create', 'update', 'delete', 'restore', 'employ', 'debut']],
        'owner' => ['owner', ['view', 'viewAny', 'create', 'update', 'delete', 'restore', 'employ', 'debut', 'manageMembers']],
    ])->with([
        'own wrestler', 'own title', 'own event', 'own event match', 'wrestler class', 'event match class', 'event class',
    ]);
});

describe('non administrators on a subject that is not promotion owned', function () {
    test('fall through to the policies whatever the context', function (string $context, string $membership, string $subject) {
        // Arrange
        [$own, $foreign] = promotionGatePromotions();
        $user = promotionGateUser($membership, $own, $foreign);
        $arguments = promotionGateArguments($subject, $own, $foreign);
        promotionGateContext($context, $own);

        // Act
        $result = Gate::forUser($user)->raw(PROMOTION_GATE_FALL_THROUGH_ABILITY, $arguments);

        // Assert
        expect($result)->toBeNull();
    })->with(['unset', 'set only', 'enforced', 'enforced without promotion'])->with(['none', 'owner'])->with([
        'none', 'venue', 'user', 'venue class', 'promotion class', 'unknown string',
    ]);

    test('are denied by the policies for real abilities', function (string $subject) {
        // Arrange
        [$own, $foreign] = promotionGatePromotions();
        $user = promotionGateUser('owner', $own, $foreign);
        $arguments = promotionGateArguments($subject, $own, $foreign);
        promotionGateContext('enforced', $own);

        // Act
        $result = Gate::forUser($user)->allows('viewAny', $arguments);

        // Assert
        expect($result)->toBeFalse();
    })->with(['venue', 'venue class']);
});
