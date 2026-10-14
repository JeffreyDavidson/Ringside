<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Http\Controllers\Events\EventsController;
use App\Livewire\Events\Tables\Main;
use App\Livewire\Matches\Tables\MatchesTable;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Storylines\Storyline;
use App\Models\Storylines\StorylineDevelopment;
use App\Models\Storylines\StorylineStep;
use Illuminate\Database\Eloquent\Collection;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Events Controller.
 *
 * @see EventsController
 */
describe('Events Controller', function () {
    /**
     * @see EventsController::show()
     */
    test('show returns a view', function () {
        // Arrange
        $event = Event::factory()->create();

        actingAs(administrator());

        // Act
        $response = get(route('events.show', $event));

        // Assert
        $response
            ->assertViewIs('events.show')
            ->assertViewHas('event', $event)
            ->assertSeeHtml('aria-label="Content workspace"')
            ->assertSee($event->name)
            ->assertSee('Status')
            ->assertSeeHtml('data-test="relationship-table-loading-placeholder"')
            ->assertSeeLivewire(MatchesTable::class);
    });

    /**
     * @see EventsController::show()
     */
    test('a user without a promotion membership cannot view an event profile', function () {
        // Arrange
        $event = Event::factory()->create();

        actingAs(basicUser());

        // Act
        $response = get(route('events.show', $event));

        // Assert
        $response
            ->assertForbidden();
    });

    /**
     * @see EventsController::show()
     */
    test('a guest cannot view an event profile', function () {
        // Arrange
        $event = Event::factory()->create();

        // Act
        $response = get(route('events.show', $event));

        // Assert
        $response
            ->assertRedirect(route('login'));
    });

    /**
     * @see EventsController::show()
     */
    test('returns 404 when event does not exist', function () {
        // Arrange
        Event::factory()->create();

        actingAs(administrator());

        // Act
        $response = get(route('events.show', 999999));

        // Assert
        $response
            ->assertNotFound();
    });
});

test('an administrator receives the events index view and table', function (): void {
    // Arrange
    actingAs(administrator());

    // Act
    $response = get(route('events.index'));

    // Assert
    $response->assertOk()->assertViewIs('events.index')
        ->assertSeeHtml('aria-label="Content workspace"')
        ->assertSeeLivewire(Main::class);
});

test('a guest cannot open the events index', function (): void {
    // Act
    $response = get(route('events.index'));

    // Assert
    $response->assertRedirect(route('login'));
});

test('a user without membership cannot open the events index', function (): void {
    // Arrange
    actingAs(basicUser());

    // Act
    $response = get(route('events.index'));

    // Assert
    $response->assertForbidden()->assertViewIs('promotions.no-membership');
});

test('an active promotion member receives the correct event profile', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $record = Event::factory()->for($promotion, 'promotion')->create();
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);
    actingAs($user);

    // Act
    $response = get(route('events.show', $record));

    // Assert
    $response->assertOk()->assertViewIs('events.show')->assertViewHas('event', $record);
});

test('the event view receives only related storylines in title and identity order', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion)->create();
    $later = Storyline::factory()->for($promotion)->create(['title' => 'Zulu']);
    $first = Storyline::factory()->for($promotion)->create(['title' => 'Alpha']);
    $tie = Storyline::factory()->for($promotion)->create(['title' => 'Alpha']);
    Storyline::factory()->for($promotion)->create(['title' => 'Unrelated']);
    $foreign = Storyline::factory()->create(['title' => 'Foreign']);
    StorylineStep::factory()->for($later)->create(['event_id' => $event->id]);
    StorylineStep::factory()->for($first)->create(['event_id' => $event->id]);
    StorylineDevelopment::factory()->for($tie)->create(['event_id' => $event->id]);
    StorylineDevelopment::factory()->for($foreign)->create(['event_id' => $event->id]);
    actingAs(administrator());

    // Act
    $response = get(route('events.show', $event));

    // Assert
    $response->assertOk()->assertViewIs('events.show')->assertViewHas('event', $event);
    $response->assertViewHas('storylines', fn (Collection $stories): bool => $stories->modelKeys() === [$first->id, $tie->id, $later->id]);
});
