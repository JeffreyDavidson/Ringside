<?php

declare(strict_types=1);

use App\Actions\Roster\Availability\CreateAction;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Livewire\Events\Tables\Main;
use App\Livewire\Matches\Tables\MatchesTable;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Storylines\Storyline;
use App\Models\Storylines\StorylineDevelopment;
use App\Models\Storylines\StorylineStep;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('index', function (): void {
    test('an active promotion member receives the events index', function (): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        $user = basicUser();
        $promotion->users()
            ->attach($user, [
                'role' => MembershipRole::Member,
                'status' => MembershipStatus::Active,
            ]);

        actingAs($user);

        // Act
        $response = get(route('events.index'));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('events.index')
            ->assertSeeHtml('<title>Events · Ringside</title>')
            ->assertSeeLivewire(Main::class);
    });

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
        $response
            ->assertRedirect(route('login'));
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
        $event = Event::factory()
            ->create(['name' => 'Fall Brawl']);

        actingAs(administrator());

        // Act
        $response = get(route('events.show', $event));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('events.show')
            ->assertSeeHtml('<title>Fall Brawl · Ringside</title>')
            ->assertViewHas('event', $event)
            ->assertSeeHtml('aria-label="Content workspace"')
            ->assertSee($event->name)
            ->assertSee('Status')
            ->assertSeeHtml('data-test="relationship-table-loading-placeholder"')
            ->assertSeeLivewire(MatchesTable::class);
    });

    test('warns about booked people who are unavailable on the event day and names them', function (): void {
        // Arrange
        $actor = administrator();
        $promotion = Promotion::factory()
            ->create();
        $event = Event::factory()
            ->for($promotion)
            ->create(['date' => now()->addWeek()]);
        [$blocked, $free] = Wrestler::factory()
            ->count(2)
            ->sequence(['name' => 'Blocked Bruiser'], ['name' => 'Free Fighter'])
            ->bookable()
            ->for($promotion)
            ->create()
            ->all();
        EventMatch::factory()
            ->forEvent($event)
            ->withCompetitors([$blocked, $free])
            ->create();
        $day = ($event->local_date ?? throw new LogicException('Expected a scheduled fixture'))->toDateString();
        resolve(CreateAction::class)->handle($blocked, $day, $day, null, $actor);
        actingAs($actor);

        // Act
        $response = get(route('events.show', $event));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('events.show')
            ->assertViewHas('calendarUnavailable', fn (SupportCollection $people): bool => $people->pluck('id')->all() === [$blocked->id])
            ->assertSee(__('availability.card_warning'))
            ->assertSeeHtml("Blocked Bruiser · {$day}")
            ->assertDontSeeHtml('Free Fighter ·');
    });

    test('shows no availability warning when everyone booked is available', function (): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        $event = Event::factory()
            ->for($promotion)
            ->create(['date' => now()->addWeek()]);
        $wrestler = Wrestler::factory()
            ->bookable()
            ->for($promotion)
            ->create();
        EventMatch::factory()
            ->forEvent($event)
            ->withCompetitors([$wrestler])
            ->create();
        actingAs(administrator());

        // Act
        $response = get(route('events.show', $event));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('events.show')
            ->assertViewHas('calendarUnavailable', fn (SupportCollection $people): bool => $people->isEmpty())
            ->assertDontSee(__('availability.card_warning'));
    });

    test('says availability is not checked while the event has no date', function (): void {
        // Arrange
        $event = Event::factory()
            ->create(['date' => null]);
        actingAs(administrator());

        // Act
        $response = get(route('events.show', $event));

        // Assert
        $response
            ->assertOk()
            ->assertSee(__('availability.not_checked'));
    });

    test('a user without a promotion membership cannot view an event profile', function () {
        // Arrange
        $event = Event::factory()
            ->create();

        actingAs(basicUser());

        // Act
        $response = get(route('events.show', $event));

        // Assert
        $response
            ->assertForbidden();
    });

    test('a guest cannot view an event profile', function () {
        // Arrange
        $event = Event::factory()
            ->create();

        // Act
        $response = get(route('events.show', $event));

        // Assert
        $response
            ->assertRedirect(route('login'));
    });

    test('returns 404 when event does not exist', function () {
        // Arrange
        Event::factory()
            ->create();

        actingAs(administrator());

        // Act
        $response = get(route('events.show', 999999));

        // Assert
        $response
            ->assertNotFound();
    });

    test('an active promotion member receives the correct event profile', function (): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        $event = Event::factory()
            ->for($promotion, 'promotion')
            ->create();

        $user = basicUser();
        $promotion->users()
            ->attach($user, [
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
        $promotion = Promotion::factory()
            ->create();
        $event = Event::factory()
            ->for($promotion)
            ->create();
        [$later, $first] = Storyline::factory()
            ->for($promotion)
            ->count(2)
            ->sequence(['title' => 'Zulu'], ['title' => 'Alpha'])
            ->has(StorylineStep::factory()
                ->for($event), 'steps')
            ->create()
            ->all();
        $tie = Storyline::factory()
            ->for($promotion)
            ->has(StorylineDevelopment::factory()
                ->for($event), 'developments')
            ->create(['title' => 'Alpha']);
        Storyline::factory()
            ->for($promotion)
            ->create(['title' => 'Unrelated']);
        Storyline::factory()
            ->has(StorylineDevelopment::factory()
                ->for($event), 'developments')
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
