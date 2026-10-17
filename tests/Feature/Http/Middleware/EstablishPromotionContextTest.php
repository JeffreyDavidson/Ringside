<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\Role;
use App\Enums\Users\UserStatus;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionInvitation;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\flushSession;
use function Pest\Laravel\get;
use function Pest\Laravel\travel;
use function Pest\Laravel\withHeaders;
use function Pest\Laravel\withSession;

function attachPromotionMembership(User $user, Promotion $promotion, MembershipStatus $status): void
{
    $promotion->users()->attach($user, [
        'role' => MembershipRole::Owner->value,
        'status' => $status->value,
    ]);
}

/**
 * Sends the Livewire update request a browser would send after the index page has loaded.
 *
 * The scoped promotion context is reset first so the update runs with the same empty
 * state as a separate PHP request instead of inheriting the context from the page load.
 *
 * @return TestResponse<Response>
 */
function sendWrestlersTableUpdate(string $search): TestResponse
{
    $page = get(route('wrestlers.index'))->assertOk();

    preg_match_all('/wire:snapshot="([^"]+)"/', (string) $page->getContent(), $matches);

    $snapshot = collect($matches[1])
        ->map(fn (string $encoded): string => html_entity_decode($encoded, ENT_QUOTES))
        ->first(fn (string $snapshot): bool => str_contains($snapshot, '"name":"wrestlers.tables.main"'));

    expect($snapshot)->not->toBeNull();

    app()->forgetScopedInstances();

    return withHeaders(['X-Livewire' => 'true'])
        ->postJson(route('default-livewire.update'), [
            'components' => [
                [
                    'snapshot' => $snapshot,
                    'updates' => ['search' => $search],
                    'calls' => [],
                ],
            ],
        ]);
}

beforeEach(function (): void {
    Route::name('middleware.promotionContextProbe')
        ->middleware(['web', 'promotion.context'])
        ->get('/promotion-context-probe', function (): array {
            $context = resolve(PromotionContextService::class);

            return [
                'enforced' => $context->isEnforced(),
                'promotion_id' => $context->current()?->id,
            ];
        });
});

describe('promotion selection', function (): void {
    test('the selected active promotion is used instead of the first membership', function (Role $role): void {
        // Arrange
        $user = User::factory()
            ->create(['role' => $role, 'status' => UserStatus::Active]);
        [$firstPromotion, $selectedPromotion] = Promotion::factory()
            ->count(2)
            ->create()
            ->all();
        attachPromotionMembership($user, $firstPromotion, MembershipStatus::Active);
        attachPromotionMembership($user, $selectedPromotion, MembershipStatus::Active);

        actingAs($user);
        withSession(['active_promotion_id' => $selectedPromotion->id]);

        // Act
        $response = get(route('middleware.promotionContextProbe'));

        // Assert
        $response
            ->assertOk()
            ->assertExactJson(['enforced' => true, 'promotion_id' => $selectedPromotion->id])
            ->assertSessionHas('active_promotion_id', $selectedPromotion->id);
    })->with(['administrator' => Role::Administrator, 'member' => Role::Basic]);

    test('the first active promotion is used when none has been selected', function (): void {
        // Arrange
        $user = basicUser();
        $firstPromotion = Promotion::factory()
            ->create();
        $suspendedPromotion = Promotion::factory()
            ->create();
        attachPromotionMembership($user, $suspendedPromotion, MembershipStatus::Suspended);
        attachPromotionMembership($user, $firstPromotion, MembershipStatus::Active);

        actingAs($user);

        // Act
        $response = get(route('wrestlers.index'));

        // Assert
        $response
            ->assertOk()
            ->assertSessionHas('active_promotion_id', $firstPromotion->id);
    });

    test('the fallback is the promotion of the oldest active membership, not the lowest promotion id', function (): void {
        // Arrange
        $user = basicUser();
        [$lowerPromotion, $higherPromotion] = Promotion::factory()
            ->count(2)
            ->create()
            ->all();
        attachPromotionMembership($user, $higherPromotion, MembershipStatus::Active);
        travel(1)->day();
        attachPromotionMembership($user, $lowerPromotion, MembershipStatus::Active);

        actingAs($user);

        // Act
        $response = get(route('wrestlers.index'));

        // Assert
        $response
            ->assertOk()
            ->assertSessionHas('active_promotion_id', $higherPromotion->id);
    });

    test('a promotion that invited the user earlier never becomes the default once they join another', function (): void {
        // Arrange
        $user = basicUser();
        [$invitingPromotion, $ownPromotion] = Promotion::factory()
            ->count(2)
            ->create()
            ->all();
        PromotionInvitation::factory()
            ->for($invitingPromotion)
            ->forEmail($user->email)
            ->create();
        travel(1)->day();
        attachPromotionMembership($user, $ownPromotion, MembershipStatus::Active);

        actingAs($user);

        // Act
        $response = get(route('wrestlers.index'));

        // Assert
        $response
            ->assertOk()
            ->assertSessionHas('active_promotion_id', $ownPromotion->id);
    });

    test('memberships created at the same moment fall back to the lowest promotion id', function (): void {
        // Arrange
        $user = basicUser();
        [$lowerPromotion, $higherPromotion] = Promotion::factory()
            ->count(2)
            ->create()
            ->all();
        attachPromotionMembership($user, $higherPromotion, MembershipStatus::Active);
        attachPromotionMembership($user, $lowerPromotion, MembershipStatus::Active);

        actingAs($user);

        // Act
        $response = get(route('wrestlers.index'));

        // Assert
        $response
            ->assertOk()
            ->assertSessionHas('active_promotion_id', $lowerPromotion->id);
    });
});

describe('remembered promotion fallback', function (): void {
    test('a remembered promotion with no membership falls back to the first active promotion', function (): void {
        // Arrange
        $user = basicUser();
        [$rememberedPromotion, $activePromotion] = Promotion::factory()
            ->count(2)
            ->create()
            ->all();
        attachPromotionMembership($user, $activePromotion, MembershipStatus::Active);

        actingAs($user);
        withSession(['active_promotion_id' => $rememberedPromotion->id]);

        // Act
        $response = get(route('dashboard'));

        // Assert
        $response
            ->assertOk()
            ->assertSessionHas('active_promotion_id', $activePromotion->id);

        expect(resolve(PromotionContextService::class)->required()->id)->toBe($activePromotion->id);
    });

    test('a remembered promotion with a suspended membership falls back to the first active promotion', function (): void {
        // Arrange
        $user = basicUser();
        [$rememberedPromotion, $activePromotion] = Promotion::factory()
            ->count(2)
            ->create()
            ->all();
        attachPromotionMembership($user, $activePromotion, MembershipStatus::Active);
        attachPromotionMembership($user, $rememberedPromotion, MembershipStatus::Suspended);

        actingAs($user);
        withSession(['active_promotion_id' => $rememberedPromotion->id]);

        // Act
        $response = get(route('dashboard'));

        // Assert
        $response
            ->assertOk()
            ->assertSessionHas('active_promotion_id', $activePromotion->id);

        expect(resolve(PromotionContextService::class)->required()->id)->toBe($activePromotion->id);
    });

    test('a remembered promotion that only invited the user falls back to the first active promotion', function (): void {
        // Arrange
        $user = basicUser();
        $invitingPromotion = Promotion::factory()
            ->create();
        $activePromotion = Promotion::factory()
            ->create();
        attachPromotionMembership($user, $activePromotion, MembershipStatus::Active);
        PromotionInvitation::factory()
            ->for($invitingPromotion)
            ->forEmail($user->email)
            ->create();

        actingAs($user);
        withSession(['active_promotion_id' => $invitingPromotion->id]);

        // Act
        $response = get(route('dashboard'));

        // Assert
        $response
            ->assertOk()
            ->assertSessionHas('active_promotion_id', $activePromotion->id);
    });

    test('a remembered promotion that no longer exists falls back to the first active promotion', function (): void {
        // Arrange
        $user = basicUser();
        $activePromotion = Promotion::factory()
            ->create();
        attachPromotionMembership($user, $activePromotion, MembershipStatus::Active);

        actingAs($user);
        withSession(['active_promotion_id' => 999_999]);

        // Act
        $response = get(route('wrestlers.index'));

        // Assert
        $response
            ->assertOk()
            ->assertSessionHas('active_promotion_id', $activePromotion->id);
    });
});

describe('requests without an eligible promotion', function (): void {
    test('users without any membership are refused before the route handler runs', function (): void {
        // Arrange
        actingAs(basicUser());

        // Act
        $response = get(route('middleware.promotionContextProbe'));

        // Assert
        $response
            ->assertForbidden()
            ->assertViewIs('promotions.no-membership')
            ->assertViewHas('invitations', fn (Collection $invitations): bool => $invitations->isEmpty());
    });

    test('the no-membership view receives only pending invitations for the signed-in user', function (): void {
        // Arrange
        $user = basicUser();
        $invitation = PromotionInvitation::factory()
            ->forEmail($user->email)
            ->create();

        PromotionInvitation::factory()
            ->forEmail('someone-else@example.com')
            ->create();
        PromotionInvitation::factory()
            ->forEmail($user->email)
            ->expired()
            ->create();

        actingAs($user);

        // Act
        $response = get(route('dashboard'));

        // Assert
        $response
            ->assertForbidden()
            ->assertViewIs('promotions.no-membership')
            ->assertViewHas('invitations', fn (Collection $invitations): bool => $invitations->modelKeys() === [$invitation->id]);
    });

    test('unauthenticated requests are rejected with a 401', function (): void {
        // Arrange
        $url = route('middleware.promotionContextProbe');

        // Act
        $response = get($url);

        // Assert
        $response
            ->assertUnauthorized();
    });

    test('users with no active promotion membership get the no-membership page', function (): void {
        // Arrange
        $user = basicUser();
        $suspendedPromotion = Promotion::factory()
            ->create();
        attachPromotionMembership($user, $suspendedPromotion, MembershipStatus::Suspended);

        actingAs($user);
        withSession(['active_promotion_id' => $suspendedPromotion->id]);

        // Act
        $response = get(route('dashboard'));

        // Assert
        $response
            ->assertForbidden()
            ->assertViewIs('promotions.no-membership');
    });

    test('the no-membership page offers a log out instead of promotion navigation that loops back to it', function (): void {
        // Arrange
        $user = basicUser();

        actingAs($user);

        // Act
        $response = get(route('dashboard'));

        // Assert
        $response
            ->assertForbidden()
            ->assertSee(__('promotions.no_membership_title'))
            ->assertSee(__('promotions.no_membership_description'))
            ->assertSeeHtml(route('logout'))
            ->assertSee(__('auth-forms.log_out'))
            ->assertDontSeeHtml(route('wrestlers.index'))
            ->assertDontSeeHtml(route('events.index'))
            ->assertDontSeeHtml(route('dashboard'));
    });
});

describe('request context isolation', function (): void {
    test('each request starts without the previous request promotion context', function (): void {
        // Arrange
        $member = basicUser();
        $promotion = Promotion::factory()
            ->create();
        attachPromotionMembership($member, $promotion, MembershipStatus::Active);
        $administrator = administrator();
        actingAs($member);
        $baseline = get(route('middleware.promotionContextProbe'));
        $baseline
            ->assertOk()
            ->assertJson(['enforced' => true]);

        flushSession();
        actingAs($administrator);

        // Act
        $response = get(route('middleware.promotionContextProbe'));

        // Assert
        $response
            ->assertOk()
            ->assertExactJson(['enforced' => false, 'promotion_id' => null]);
    });
});

describe('livewire updates', function (): void {
    test('livewire updates keep the active promotion context for promotion users', function (bool $isAdministrator, MembershipRole $role) {
        // Arrange
        $promotion = Promotion::factory()->create();
        $otherPromotion = Promotion::factory()->create();
        Wrestler::factory()->for($promotion, 'promotion')->create(['name' => 'Ownedwrestler Alpha']);
        Wrestler::factory()->for($otherPromotion, 'promotion')->create(['name' => 'Foreignwrestler Alpha']);
        $user = $isAdministrator
            ? User::factory()->administrator()->create(['status' => UserStatus::Active])
            : basicUser();
        $promotion->users()->attach($user, [
            'role' => $role,
            'status' => MembershipStatus::Active,
        ]);
        actingAs($user);

        // Act
        $response = sendWrestlersTableUpdate('Alpha');

        // Assert
        $response->assertOk();
        expect($response->json('components.0.effects.html'))
            ->toBeString()
            ->toContain('Ownedwrestler Alpha')
            ->not->toContain('Foreignwrestler Alpha');
    })->with([
        'administrator in the promotion' => [true, MembershipRole::Owner],
        'member of the promotion' => [false, MembershipRole::Member],
    ]);
});
