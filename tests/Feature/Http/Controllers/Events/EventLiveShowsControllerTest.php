<?php

declare(strict_types=1);

use App\Actions\Events\PrepareLiveShowAction;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Events\Event;
use App\Models\Events\EventLiveRun;
use App\Models\Promotions\Promotion;
use App\ViewModels\EventPackViewModel;
use App\ViewModels\LiveShowViewModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('the live show page opens without preparing or changing a show', function (): void {
    // Arrange
    $event = Event::factory()
        ->create();

    actingAs(administrator());

    // Act
    $response = get(route('events.live-show.show', $event));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.live')
        ->assertViewHas('event', $event)
        ->assertSee('Live show')
        ->assertSee('Save and review');

    expect(EventLiveRun::query()
        ->count())->toBe(0);
});

test('an active member receives the live view for their own event', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();

    $user = basicUser();
    $promotion->users()
        ->attach($user, [
            'role' => MembershipRole::Member,
            'status' => MembershipStatus::Active,
        ]);

    actingAs($user);

    // Act
    $response = get(route('events.live-show.show', $event));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.live')
        ->assertViewHas('event', $event);

    expect(EventLiveRun::query()
        ->count())->toBe(0);
});

test('guests cannot open a live show', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();

    // Act
    $response = get(route('events.live-show.show', $event));

    // Assert
    $response
        ->assertRedirect(route('login'));

    expect(EventLiveRun::query()
        ->count())->toBe(0);
});

test('foreign promotions cannot open a live show', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();
    $other = Promotion::factory()
        ->create();
    actingAsPromotionMember($other, MembershipRole::Manager);

    // Act
    $response = get(route('events.live-show.show', $event));

    // Assert
    $response
        ->assertNotFound();

    expect(EventLiveRun::query()
        ->count())->toBe(0);
});

test('a member live response removes crew cues and counts from its view data', function (): void {
    // Arrange
    require_once __DIR__.'/../../../../Fixtures/production.php';
    $fixture = productionFixture();
    $event = $fixture['event'];
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint']);
    $member = basicUser();
    Promotion::query()->findOrFail($event->promotion_id)->users()->attach($member, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);
    actingAs($member);

    // Act
    $response = get(route('events.live-show.show', $event));

    // Assert
    $response->assertOk()->assertDontSee('PRIVATE-CREW-7Q')->assertDontSee('PRIVATE-CUE-7Q');
    $screen = resolve(LiveShowViewModel::class)->data($event);
    expect(json_encode(data_get($screen, 'run.content')))->not->toContain('PRIVATE-CREW-7Q', 'PRIVATE-CUE-7Q')
        ->and(data_get($screen, 'run.content'))->not->toHaveKey('production')
        ->and($screen['current'])->toBeNull()
        ->and($screen['next'])->not->toHaveKey('cues');
});
