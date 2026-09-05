<?php

declare(strict_types=1);

use App\Enums\MatchType;
use App\Livewire\Matches\Tables\Main;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

it('renders an empty state without matches', function (): void {
    // Act
    $component = livewire(Main::class);

    // Assert
    $component
        ->assertSuccessful()
        ->assertSee('No records found.');
});

it('orders matches by newest event and match number while eager loading display relationships', function (): void {
    // Arrange
    $latestEvent = Event::factory()->create(['date' => Date::tomorrow()]);
    $earliestEvent = Event::factory()->create(['date' => Date::yesterday()]);
    $wrestler = Wrestler::factory()->create();
    $secondLatestMatch = EventMatch::factory()
        ->forEvent($latestEvent)
        ->withMatchNumber(2)
        ->withCompetitors([$wrestler])
        ->create();
    $firstLatestMatch = EventMatch::factory()
        ->forEvent($latestEvent)
        ->withMatchNumber(1)
        ->create();
    $earliestMatch = EventMatch::factory()
        ->forEvent($earliestEvent)
        ->withMatchNumber(1)
        ->create();

    // Act
    $matches = (new Main())->builder()->get();
    $renderedSecondLatestMatch = $matches->firstOrFail(
        fn (EventMatch $match): bool => $match->is($secondLatestMatch),
    );
    $competitor = $renderedSecondLatestMatch->competitors->firstOrFail();

    // Assert
    expect($matches->pluck('id')->all())->toBe([
        $firstLatestMatch->id,
        $secondLatestMatch->id,
        $earliestMatch->id,
    ])->and($matches->every->relationLoaded('event'))->toBeTrue()
        ->and($matches->every->relationLoaded('competitors'))->toBeTrue()
        ->and($matches->every->relationLoaded('winningSide'))->toBeTrue()
        ->and($competitor->relationLoaded('competitor'))->toBeTrue()
        ->and($competitor->relationLoaded('side'))->toBeTrue();
});

it('renders event, match type, competitors, and an empty result', function (): void {
    // Arrange
    $event = Event::factory()->create(['name' => 'Summer Spectacular']);
    $wrestler = Wrestler::factory()->create(['name' => 'Singles Wrestler']);
    $tagTeam = TagTeam::factory()->create(['name' => 'Tag Team']);
    EventMatch::factory()
        ->forEvent($event)
        ->withMatchNumber(3)
        ->withMatchType(MatchType::TagTeam)
        ->withCompetitors([$wrestler, $tagTeam])
        ->create();

    // Act
    $component = livewire(Main::class);

    // Assert
    $component
        ->assertSuccessful()
        ->assertSee('Summer Spectacular')
        ->assertSee(route('events.show', $event))
        ->assertSee('3')
        ->assertSee(MatchType::TagTeam->label())
        ->assertSee('Singles Wrestler')
        ->assertSee('Tag Team')
        ->assertSee('N/A');
});

it('deletes a match', function (): void {
    // Arrange
    $match = EventMatch::factory()->create();

    // Act
    $component = livewire(Main::class);
    $component->call('delete', $match);

    // Assert
    $component->assertSuccessful();
    $this->assertSoftDeleted($match);
});

it('forbids users without match access', function (string $actor): void {
    // Arrange
    if ($actor === 'guest') {
        Auth::logout();
    } else {
        actingAs(basicUser());
    }

    // Act
    $component = livewire(Main::class);

    // Assert
    $component->assertForbidden();
})->with([
    'guest' => ['guest'],
    'basic user' => ['basic user'],
]);
