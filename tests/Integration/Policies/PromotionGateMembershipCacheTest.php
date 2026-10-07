<?php

declare(strict_types=1);

use App\Actions\Promotions\AcceptPromotionInvitationAction;
use App\Actions\Promotions\InvitePromotionMemberAction;
use App\Actions\Promotions\SwitchActivePromotionAction;
use App\Actions\Promotions\UpdatePromotionMemberRoleAction;
use App\Actions\Promotions\UpdatePromotionMemberStatusAction;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Livewire\Events\Tables\Main as EventsTable;
use App\Livewire\Wrestlers\Tables\Main as WrestlersTable;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Gate;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\withoutVite;
use function Pest\Livewire\livewire;

/**
 * Join the promotion and establish its enforced context the way EstablishPromotionContext does.
 */
function memberInEnforcedContext(Promotion $promotion, MembershipRole $role, MembershipStatus $status = MembershipStatus::Active): User
{
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => $role, 'status' => $status]);
    actingAs($user);

    $context = app(PromotionContextService::class);
    $context->set($user->promotions()->firstOrFail());
    $context->enforce();

    return $user;
}

/**
 * @return array{int, int} Total queries and promotion_user queries made by the callback.
 */
function countQueries(Closure $callback): array
{
    $queries = collect(queriesDuring($callback));

    return [$queries->count(), $queries->filter(fn (string $sql): bool => str_contains($sql, 'promotion_user'))->count()];
}

describe('table query counts', function (): void {
    test('wrestlers table authorization queries do not grow with the page size', function (MembershipRole $role): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        Wrestler::factory()->count(25)->for($promotion, 'promotion')->create();
        memberInEnforcedContext($promotion, $role);
        $component = livewire(WrestlersTable::class);

        // Act
        [$smallPage, $smallPageMembershipQueries] = countQueries(fn () => $component->set('perPage', 5));
        [$largePage, $largePageMembershipQueries] = countQueries(fn () => $component->set('perPage', 25));

        // Assert
        expect($largePage)->toBe($smallPage)
            ->and($smallPageMembershipQueries)->toBeLessThanOrEqual(1)
            ->and($largePageMembershipQueries)->toBeLessThanOrEqual(1);
    })->with([MembershipRole::Owner, MembershipRole::Manager, MembershipRole::Member]);

    test('events table authorization queries do not grow with the page size', function (MembershipRole $role): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        Event::factory()->count(25)->for($promotion, 'promotion')->create();
        memberInEnforcedContext($promotion, $role);
        $component = livewire(EventsTable::class);

        // Act
        [$smallPage, $smallPageMembershipQueries] = countQueries(fn () => $component->set('perPage', 5));
        [$largePage, $largePageMembershipQueries] = countQueries(fn () => $component->set('perPage', 25));

        // Assert
        expect($largePage)->toBe($smallPage)
            ->and($smallPageMembershipQueries)->toBeLessThanOrEqual(1)
            ->and($largePageMembershipQueries)->toBeLessThanOrEqual(1);
    })->with([MembershipRole::Owner, MembershipRole::Manager, MembershipRole::Member]);
});

describe('authorization outcomes', function (): void {
    test('roles are allowed or denied per ability for own and foreign promotion subjects', function (MembershipRole $role, string $ability): void {
        // Arrange
        $own = Promotion::factory()->create();
        $foreign = Promotion::factory()->create();
        $ownWrestler = Wrestler::factory()->for($own, 'promotion')->create();
        $foreignWrestler = Wrestler::factory()->for($foreign, 'promotion')->create();
        $user = memberInEnforcedContext($own, $role);

        // Act
        $ownResult = Gate::forUser($user)->allows($ability, $ownWrestler);
        $classResult = Gate::forUser($user)->allows($ability, Wrestler::class);
        $foreignResult = Gate::forUser($user)->allows($ability, $foreignWrestler);

        // Assert
        expect($ownResult)->toBe($role->allows($ability))
            ->and($classResult)->toBe($role->allows($ability))
            ->and($foreignResult)->toBeFalse();
    })->with([MembershipRole::Owner, MembershipRole::Manager, MembershipRole::Member])
        ->with(['viewAny', 'view', 'update', 'delete', 'employ', 'manageMembers']);

    test('suspended memberships are denied', function (MembershipRole $role): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $wrestler = Wrestler::factory()->for($promotion, 'promotion')->create();
        $user = memberInEnforcedContext($promotion, $role, MembershipStatus::Suspended);

        // Act
        $wrestlerResult = Gate::forUser($user)->allows('view', $wrestler);
        $promotionResult = Gate::forUser($user)->allows('view', $promotion);

        // Assert
        expect($wrestlerResult)->toBeFalse()
            ->and($promotionResult)->toBeFalse();
    })->with([MembershipRole::Owner, MembershipRole::Manager, MembershipRole::Member]);

    test('the promotion subject resolves a foreign membership with one memoised query', function (): void {
        // Arrange
        $own = Promotion::factory()->create();
        $foreign = Promotion::factory()->create();
        $user = memberInEnforcedContext($own, MembershipRole::Member);
        $foreign->users()->attach($user, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);

        // Act
        [, $membershipQueries] = countQueries(function () use ($user, $foreign): void {
            Gate::forUser($user)->allows('update', $foreign);
            Gate::forUser($user)->allows('manageMembers', $foreign);
        });

        // Assert
        expect($membershipQueries)->toBe(1)
            ->and(Gate::forUser($user)->allows('update', $foreign))->toBeTrue();
    });
});

describe('staleness within a request', function (): void {
    test('role changes are reflected immediately', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $user = memberInEnforcedContext($promotion, MembershipRole::Owner);
        $wrestler = Wrestler::factory()->for($promotion, 'promotion')->create();
        $other = basicUser();
        $promotion->users()->attach($other, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);
        $before = Gate::forUser($user)->allows('update', $wrestler);

        // Act
        app(UpdatePromotionMemberRoleAction::class)->handle($promotion, $user, MembershipRole::Member);
        $after = Gate::forUser($user)->allows('update', $wrestler);

        // Assert
        expect($before)->toBeTrue()
            ->and($after)->toBeFalse();
    });

    test('suspension and reactivation are reflected immediately', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $user = memberInEnforcedContext($promotion, MembershipRole::Manager);
        $wrestler = Wrestler::factory()->for($promotion, 'promotion')->create();
        $before = Gate::forUser($user)->allows('view', $wrestler);

        // Act
        app(UpdatePromotionMemberStatusAction::class)->handle($promotion, $user, MembershipStatus::Suspended);
        $suspended = Gate::forUser($user)->allows('view', $wrestler);
        app(UpdatePromotionMemberStatusAction::class)->handle($promotion, $user, MembershipStatus::Active);
        $reactivated = Gate::forUser($user)->allows('view', $wrestler);

        // Assert
        expect($before)->toBeTrue()
            ->and($suspended)->toBeFalse()
            ->and($reactivated)->toBeTrue();
    });

    test('an invitation grants nothing until it is accepted, and acceptance is reflected immediately', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $other = Promotion::factory()->create();
        $user = memberInEnforcedContext($other, MembershipRole::Owner);
        $before = Gate::forUser($user)->allows('view', $promotion);

        // Act
        app(InvitePromotionMemberAction::class)->handle($promotion, $user->email, MembershipRole::Member, User::factory()->create());
        $invited = Gate::forUser($user)->allows('view', $promotion);
        app(AcceptPromotionInvitationAction::class)->handle($promotion, $user);
        $accepted = Gate::forUser($user)->allows('view', $promotion);

        // Assert
        expect($before)->toBeFalse()
            ->and($invited)->toBeFalse()
            ->and($accepted)->toBeTrue();
    });

    test('switching promotions authorizes against the new promotion', function (): void {
        // Arrange
        $first = Promotion::factory()->create();
        $second = Promotion::factory()->create();
        $user = memberInEnforcedContext($first, MembershipRole::Owner);
        $second->users()->attach($user, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);
        $firstWrestler = Wrestler::factory()->for($first, 'promotion')->create();
        $secondWrestler = Wrestler::factory()->for($second, 'promotion')->create();
        $before = Gate::forUser($user)->allows('update', $firstWrestler);

        // Act
        app(SwitchActivePromotionAction::class)->handle($user, $second->id, new Store('test', new ArraySessionHandler(10)));

        // Assert
        expect($before)->toBeTrue()
            ->and(Gate::forUser($user)->allows('update', $firstWrestler))->toBeFalse()
            ->and(Gate::forUser($user)->allows('update', $secondWrestler))->toBeFalse()
            ->and(Gate::forUser($user)->allows('view', $secondWrestler))->toBeTrue();
    });

    test('a new request re-reads memberships changed outside the request', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $user = memberInEnforcedContext($promotion, MembershipRole::Owner);
        $wrestler = Wrestler::factory()->for($promotion, 'promotion')->create();
        Gate::forUser($user)->allows('update', $wrestler);
        $promotion->users()->updateExistingPivot($user->getKey(), ['status' => MembershipStatus::Suspended]);

        withoutVite();

        // Act
        $response = get(route('wrestlers.index'));

        // Assert
        $response->assertForbidden();
    });
});

describe('page load queries', function (): void {
    test('a page load resolves the membership with a single query', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $user = basicUser();
        actingAs($user);
        $promotion->users()->attach($user, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);

        withoutVite();

        // Act
        [$total, $membershipQueries] = countQueries(fn () => get(route('wrestlers.index'))->assertOk());

        // Assert
        // One promotion_user query, one promotion_invitations query for the sidebar indicator, and the user re-read.
        expect([$total, $membershipQueries])->toBe([3, 1]);
    });
});
