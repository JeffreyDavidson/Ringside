<?php

declare(strict_types=1);

use App\Data\Storylines\SourceData;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Models\Promotions\Promotion;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('only owners and managers can open creative planning', function (MembershipRole $role, int $status) {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => $role, 'status' => MembershipStatus::Active]);
    actingAs($user);

    // Act
    $response = get(route('storylines.index'));

    // Assert
    $response->assertStatus($status);
})->with([
    'owner' => [MembershipRole::Owner, 200],
    'booker' => [MembershipRole::Manager, 200],
    'viewer' => [MembershipRole::Member, 403],
]);

require_once __DIR__.'/../../../../Fixtures/storylines.php';
test('a foreign story ID and a missing ID are both hidden while the owner can read the real story', function () {
    $f = storylineSourceFixture();
    get(route('storylines.show', $f['story']))->assertOk()->assertSee('Private betrayal');
    $foreign = Promotion::factory()->create();
    $attacker = basicUser();
    $foreign->users()->attach($attacker, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);
    session()->flush();
    actingAs($attacker);
    expect(Auth::id())->toBe($attacker->id)
        ->and($attacker->refresh()->status)->toBe(UserStatus::Active);
    $response = get(route('storylines.show', $f['story']));
    expect($response->headers->get('Location'))->toBeNull();
    $response->assertNotFound();
    get(route('storylines.show', 999999))->assertNotFound();
});
test('read-only members see ordinary event surfaces without private creative markers or hints', function () {
    $f = storylineSourceFixture();
    plannedStorylineStep($f['story'], new SourceData($f['event']->id, $f['match']->id));
    $ownerPage = get(route('events.show', $f['event']));
    $ownerPage->assertSee('Related storylines')->assertSee('Private betrayal');
    $member = basicUser();
    requiredModel($f['story']->promotion)->users()->attach($member, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);
    session()->flush();
    actingAs($member);
    expect(Auth::id())->toBe($member->id)
        ->and($member->refresh()->status)->toBe(UserStatus::Active);
    $response = get(route('storylines.show', $f['story']));
    expect($response->headers->get('Location'))->toBeNull();
    $response->assertForbidden();
    foreach (['events.show', 'events.pack.show', 'events.live-show.show'] as $route) {
        get(route($route, $f['event']))->assertOk()->assertDontSee('Private betrayal')->assertDontSee('Related storylines');
    }
    get(route('events.pack.show', ['event' => $f['event'], 'print' => 1]))->assertOk()->assertDontSee('Private betrayal');
    expect($f['story']->revisions()->count())->toBe(1);
});
