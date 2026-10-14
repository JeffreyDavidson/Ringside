<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Livewire\Promotions\Members\Manage;
use App\Livewire\Promotions\Tables\Main;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionInvitation;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('shows a promotion detail page and its member manager to platform administrators', function () {
    // Arrange
    $promotion = Promotion::factory()->create(['name' => 'Ringside Wrestling']);

    actingAs(administrator());

    // Act
    $response = get(route('promotions.show', $promotion));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('promotions.show')->assertViewHas('promotion', $promotion)
        ->assertSee('Ringside Wrestling')
        ->assertSeeHtml('data-test="promotion-members-loading-placeholder"')
        ->assertSeeLivewire(Manage::class);
});

it('a user without membership cannot open promotion details', function () {
    // Arrange
    $promotion = Promotion::factory()->create();

    actingAs(basicUser());

    // Act
    $response = get(route('promotions.show', $promotion));

    // Assert
    $response
        ->assertForbidden();
});

it('redirects guests to login from the promotion member page', function () {
    // Arrange
    $promotion = Promotion::factory()->create();

    // Act
    $response = get(route('promotions.show', $promotion));

    // Assert
    $response
        ->assertRedirect(route('login'));
});

it('counts only members on the promotion page, not invitations', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    PromotionInvitation::factory()->for($promotion)->create();
    $member = User::factory()->create(['status' => UserStatus::Active]);
    $promotion->users()->attach($member, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);

    actingAs(administrator());

    // Act
    $response = get(route('promotions.show', $promotion));

    // Assert
    $response->assertOk()->assertViewHas('promotion', fn (Promotion $shown): bool => $shown->memberships_count === 1);
});

test('an administrator receives the promotion directory and table', function (): void {
    // Arrange
    actingAs(administrator());

    // Act
    $response = get(route('promotions.index'));

    // Assert
    $response->assertOk()->assertViewIs('promotions.index')
        ->assertSeeLivewire(Main::class)
        ->assertSeeHtml('data-test="table-loading-placeholder"')->assertSee(__('core.loading_table'));
});

test('an active member receives their promotion but cannot open the platform directory', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);
    actingAs($user);

    // Act
    $response = get(route('promotions.show', $promotion));
    $directoryResponse = get(route('promotions.index'));

    // Assert
    $response->assertOk()->assertViewIs('promotions.show')->assertViewHas('promotion', $promotion);
    $directoryResponse->assertForbidden();
});

test('guests cannot open the platform promotion directory', function (): void {
    // Act
    $response = get(route('promotions.index'));

    // Assert
    $response->assertRedirect(route('login'));
});

test('users without membership cannot open the promotion directory', function (): void {
    // Arrange
    actingAs(basicUser());

    // Act
    $response = get(route('promotions.index'));

    // Assert
    $response->assertForbidden()->assertViewIs('promotions.no-membership');
});
