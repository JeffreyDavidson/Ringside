<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
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

describe('index', function (): void {
    test('an administrator receives the events index view and table', function (): void {
        // Arrange
        actingAs(administrator());

        // Act
        $response = get(route('events.index'));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('events.index')
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
        $response
            ->assertForbidden()
            ->assertViewIs('promotions.no-membership');
    });
});

describe('show', function (): void {
    test('renders the event profile and its related data', function () {
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

    test('a guest cannot view an event profile', function () {
        // Arrange
        $event = Event::factory()->create();

        // Act
        $response = get(route('events.show', $event));

        // Assert
        $response
            ->assertRedirect(route('login'));
    });

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

    test('an active promotion member receives the correct event profile', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $event = Event::factory()->for($promotion, 'promotion')->create();

        $user = basicUser();
        $promotion->users()->attach($user, [
            'role' => MembershipRole::Member,
            'status' => MembershipStatus::Active,
        ]);

        actingAs($user);

        // Act
        $response = get(route('events.show', $event));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('events.show')
            ->assertViewHas('event', $event);
    });

    test('the event view receives only related storylines in title and identity order', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $event = Event::factory()->for($promotion)->create();
        [$later, $first] = Storyline::factory()
            ->for($promotion)
            ->count(2)
            ->sequence(['title' => 'Zulu'], ['title' => 'Alpha'])
            ->has(StorylineStep::factory()->for($event), 'steps')
            ->create()
            ->all();
        $tie = Storyline::factory()
            ->for($promotion)
            ->has(StorylineDevelopment::factory()->for($event), 'developments')
            ->create(['title' => 'Alpha']);
        Storyline::factory()->for($promotion)->create(['title' => 'Unrelated']);
        Storyline::factory()
            ->has(StorylineDevelopment::factory()->for($event), 'developments')
            ->create(['title' => 'Foreign']);

        actingAs(administrator());

        // Act
        $response = get(route('events.show', $event));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('events.show')
            ->assertViewHas('event', $event)
            ->assertViewHas('storylines', fn (Collection $stories): bool => $stories->modelKeys() === [$first->id, $tie->id, $later->id]);
    });
});
