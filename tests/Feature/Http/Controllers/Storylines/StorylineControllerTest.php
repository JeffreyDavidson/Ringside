<?php

declare(strict_types=1);

use App\Data\Storylines\SourceData;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Users\UserStatus;
use App\Models\Promotions\Promotion;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

require_once __DIR__.'/../../../../Fixtures/storylines.php';

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Storylines\Storyline;

describe('index', function (): void {
    test('owners and managers can open creative planning', function (MembershipRole $role): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();

        actingAsPromotionMember($promotion, $role);

        // Act
        $response = get(route('storylines.index'));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('storylines.index');
    })->with(['owner' => MembershipRole::Owner, 'manager' => MembershipRole::Manager]);

    test('read-only members cannot open creative planning', function (): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        actingAsPromotionMember($promotion, MembershipRole::Member);

        // Act
        $response = get(route('storylines.index'));

        // Assert
        $response
            ->assertForbidden();
    });

    test('guests cannot open storylines.index', function (): void {
        // Arrange

        // Act
        $response = get(route('storylines.index'));

        // Assert
        $response
            ->assertRedirect(route('login'));
    });

    test('users without memberships cannot open creative planning', function (): void {
        // Arrange
        actingAs(basicUser());

        // Act
        $response = get(route('storylines.index'));

        // Assert
        $response
            ->assertForbidden()
            ->assertViewIs('promotions.no-membership');
    });

});

describe('show', function (): void {
    test('an administrator receives the authorized story and view payload', function (): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        $story = Storyline::factory()
            ->for($promotion)
            ->create(['title' => 'Private betrayal']);

        actingAs(administrator());

        // Act
        $response = get(route('storylines.show', $story));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('storylines.show')
            ->assertViewHas('storyline', $story)
            ->assertSee('Private betrayal');
    });

    test('foreign and missing story identifiers are indistinguishable', function (): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        $story = Storyline::factory()
            ->for($promotion)
            ->create(['title' => 'Private betrayal']);
        $foreignPromotion = Promotion::factory()
            ->create();

        $attacker = actingAsPromotionMember($foreignPromotion, MembershipRole::Owner);
        $missingUrl = route('storylines.show', 999999);

        // Compare a missing identifier with an existing foreign identifier.
        $baselineResponse = get($missingUrl);
        $baselineResponse
            ->assertNotFound();

        // Act
        $response = get(route('storylines.show', $story));

        // Assert
        $response
            ->assertNotFound()
            ->assertHeaderMissing('Location');

        expect(Auth::id())->toBe($attacker->id)
            ->and($attacker->refresh()->status)
            ->toBe(UserStatus::Active);
    });

    test('active members cannot open a private story', function (): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        $story = Storyline::factory()
            ->for($promotion)
            ->create(['title' => 'Private betrayal']);

        $member = actingAsPromotionMember($promotion, MembershipRole::Member);

        // Act
        $response = get(route('storylines.show', $story));

        // Assert
        $response
            ->assertForbidden()
            ->assertHeaderMissing('Location');

        expect(Auth::id())->toBe($member->id)
            ->and($member->refresh()->status)
            ->toBe(UserStatus::Active);
    });

    test('guests cannot open a private story', function (): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        $story = Storyline::factory()
            ->for($promotion)
            ->create(['title' => 'Private betrayal']);

        // Act
        $response = get(route('storylines.show', $story));

        // Assert
        $response
            ->assertRedirect(route('login'));
    });

});

describe('event privacy', function (): void {
    test('authorized event pages include their related private story', function (): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        $story = Storyline::factory()
            ->for($promotion)
            ->create(['title' => 'Private betrayal']);
        $event = Event::factory()
            ->for($promotion)
            ->create(['name' => 'Harbor Show']);
        $match = EventMatch::factory()
            ->for($event)
            ->create();

        actingAs(administrator());
        plannedStorylineStep($story, new SourceData($event->id, $match->id));

        // Act
        $response = get(route('events.show', $event));

        // Assert
        $response
            ->assertOk()
            ->assertSee('Related storylines')
            ->assertSee('Private betrayal');
    });

    test('read-only event surfaces omit creative markers and hints', function (string $routeName, bool $print): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        $story = Storyline::factory()
            ->for($promotion)
            ->create(['title' => 'Private betrayal']);
        $event = Event::factory()
            ->for($promotion)
            ->create(['name' => 'Harbor Show']);
        $match = EventMatch::factory()
            ->for($event)
            ->create();

        actingAs(administrator());
        plannedStorylineStep($story, new SourceData($event->id, $match->id));

        actingAsPromotionMember($promotion, MembershipRole::Member);

        // Act
        $response = get(route($routeName, ['event' => $event, 'print' => $print]));

        // Assert
        $response
            ->assertOk()
            ->assertDontSee('Private betrayal')
            ->assertDontSee('Related storylines');

        expect($story->revisions()
            ->count())->toBe(1);
    })->with(['event' => ['events.show', false], 'pack' => ['events.pack.show', false], 'live' => ['events.live-show.show', false], 'print' => ['events.pack.show', true]]);

});
