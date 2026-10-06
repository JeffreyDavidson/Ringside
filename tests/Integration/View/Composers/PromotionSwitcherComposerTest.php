<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionInvitation;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View as ViewFactory;

use function Pest\Laravel\actingAs;

function composePromotionSwitcher(string $viewName): View
{
    $view = ViewFactory::first([$viewName]);
    ViewFactory::callComposer($view);

    return $view;
}

function joinPromotion(User $user, string $name, MembershipStatus $status = MembershipStatus::Active): Promotion
{
    $promotion = Promotion::factory()->create(['name' => $name]);
    $promotion->users()->attach($user, [
        'role' => MembershipRole::Member->value,
        'status' => $status->value,
    ]);

    return $promotion;
}

afterEach(function () {
    app(PromotionContextService::class)->clear();
});

it('provides no promotions to a guest', function (string $viewName) {
    Promotion::factory()->create();

    $view = composePromotionSwitcher($viewName);

    expect($view->getData()['promotionSwitcherPromotions'])->toBeEmpty()
        ->and($view->getData()['activePromotionId'])->toBeNull();
})->with([
    'sidebar index' => 'components.sidebar.index',
    'layout header' => 'components.layouts.partials.header',
]);

it('lists only the active memberships of the user ordered by name', function () {
    $user = User::factory()->create();
    $second = joinPromotion($user, 'Beta Wrestling');
    $first = joinPromotion($user, 'Alpha Wrestling');
    joinPromotion($user, 'Gamma Wrestling', MembershipStatus::Suspended);
    Promotion::factory()->create(['name' => 'Delta Wrestling']);
    actingAs($user);

    $view = composePromotionSwitcher('components.sidebar.index');

    expect($view->getData()['promotionSwitcherPromotions']->pluck('id')->all())
        ->toBe([$first->id, $second->id]);
});

it('marks the promotion from the context as active', function () {
    $user = User::factory()->create();
    joinPromotion($user, 'Alpha Wrestling');
    $current = joinPromotion($user, 'Beta Wrestling');
    joinPromotion($user, 'Gamma Wrestling');
    app(PromotionContextService::class)->set($current);
    actingAs($user)->withSession(['active_promotion_id' => 999]);

    $view = composePromotionSwitcher('components.sidebar.index');

    expect($view->getData()['activePromotionId'])->toBe($current->id);
});

it('marks the promotion remembered in the session as active', function () {
    $user = User::factory()->create();
    joinPromotion($user, 'Alpha Wrestling');
    $remembered = joinPromotion($user, 'Beta Wrestling');
    actingAs($user)->withSession(['active_promotion_id' => (string) $remembered->id]);

    $view = composePromotionSwitcher('components.sidebar.index');

    expect($view->getData()['activePromotionId'])->toBe($remembered->id);
});

it('falls back to the first promotion when nothing valid is remembered', function (mixed $remembered) {
    $user = User::factory()->create();
    $first = joinPromotion($user, 'Alpha Wrestling');
    joinPromotion($user, 'Beta Wrestling');
    actingAs($user)->withSession(['active_promotion_id' => $remembered]);

    $view = composePromotionSwitcher('components.sidebar.index');

    expect($view->getData()['activePromotionId'])->toBe($first->id);
})->with([
    'nothing remembered' => [null],
    'not a number' => ['beta'],
]);

it('lists the pending invitations apart from the active promotions, with one membership query and one invitation query', function () {
    // Arrange
    $user = User::factory()->create();
    $active = joinPromotion($user, 'Alpha Wrestling');
    $invitedBy = Promotion::factory()->create(['name' => 'Beta Wrestling']);
    $invitation = PromotionInvitation::factory()->for($invitedBy)->forEmail($user->email)->create();
    PromotionInvitation::factory()->for($active)->forEmail('someone.else@example.test')->create();
    joinPromotion($user, 'Gamma Wrestling', MembershipStatus::Suspended);
    actingAs($user);
    DB::flushQueryLog();
    DB::enableQueryLog();

    // Act
    $view = composePromotionSwitcher('components.sidebar.index');
    $queries = collect(DB::getQueryLog())->pluck('query');
    $membershipQueries = $queries->filter(fn (mixed $sql): bool => is_string($sql) && str_contains($sql, 'promotion_user'))->count();
    $invitationQueries = $queries->filter(fn (mixed $sql): bool => is_string($sql) && str_contains(normalizedSql($sql), 'from "promotion_invitations"'))->count();
    DB::disableQueryLog();

    // Assert
    expect($view->getData()['promotionSwitcherPromotions']->pluck('id')->all())->toBe([$active->id])
        ->and($view->getData()['promotionInvitations']->pluck('id')->all())->toBe([$invitation->id])
        ->and($membershipQueries)->toBe(1)
        ->and($invitationQueries)->toBe(1);
});

it('provides no invitations to a guest', function () {
    // Arrange
    Promotion::factory()->create();

    // Act
    $view = composePromotionSwitcher('components.sidebar.index');

    // Assert
    expect($view->getData()['promotionInvitations'])->toBeEmpty();
});

it('has no active promotion for a user without memberships', function () {
    $user = User::factory()->create();
    actingAs($user);

    $view = composePromotionSwitcher('components.sidebar.index');

    expect($view->getData()['promotionSwitcherPromotions'])->toBeEmpty()
        ->and($view->getData()['activePromotionId'])->toBeNull();
});

it('leaves an expired invitation out of the switcher', function () {
    // Arrange
    $user = User::factory()->create();
    joinPromotion($user, 'Alpha Wrestling');
    $pending = PromotionInvitation::factory()->forEmail($user->email)->create();
    PromotionInvitation::factory()->forEmail($user->email)->expired()->create();
    actingAs($user);

    // Act
    $view = composePromotionSwitcher('components.sidebar.index');

    // Assert
    expect($view->getData()['promotionInvitations']->pluck('id')->all())->toBe([$pending->id]);
});
