<?php

declare(strict_types=1);

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleOwnerType;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Livewire\Events\Tables\Main as EventsTable;
use App\Livewire\Managers\Tables\Main as ManagersTable;
use App\Livewire\Matches\Tables\MatchesTable;
use App\Livewire\Referees\Tables\Main as RefereesTable;
use App\Livewire\Stables\Tables\Main as StablesTable;
use App\Livewire\TagTeams\Tables\Main as TagTeamsTable;
use App\Livewire\Titles\Tables\Main as TitlesTable;
use App\Livewire\Venues\Tables\Main as VenuesTable;
use App\Livewire\Wrestlers\Tables\Main as WrestlersTable;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Lifecycle\LifecycleTransition;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Support\Facades\Lang;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

test('table deletions use the typed lifecycle action', function (LifecycleOwnerType $ownerType, Closure $createOwner, string $component, string $translationKey, ?Closure $componentParameters = null) {
    $owner = $createOwner();

    actingAs(administrator());

    livewire($component, $componentParameters === null ? [] : $componentParameters($owner))
        ->call('delete', $owner)
        ->assertHasNoErrors()
        ->assertDispatched(
            'flash-message',
            type: 'status',
            message: __($translationKey),
        );

    $transition = LifecycleTransition::query()
        ->where('subject_type', $ownerType->morphAlias())
        ->where('subject_id', $owner->getKey())
        ->sole();
    $owner->refresh();

    expect(Lang::has($translationKey))->toBeTrue()
        ->and($owner->trashed())->toBeTrue()
        ->and($transition->dimension)->toBe(LifecycleDimension::Deletion)
        ->and($transition->transition)->toBe(LifecycleTransitionType::Deleted);
})->with([
    'event' => [LifecycleOwnerType::Event, fn () => Event::factory()->create(), EventsTable::class, 'events.actions.deleted'],
    'manager' => [LifecycleOwnerType::Manager, fn () => Manager::factory()->create(), ManagersTable::class, 'managers.actions.deleted'],
    'match' => [LifecycleOwnerType::Match, fn () => EventMatch::factory()->create(), MatchesTable::class, 'matches.actions.deleted', fn (EventMatch $owner) => ['eventId' => $owner->event_id]],
    'referee' => [LifecycleOwnerType::Referee, fn () => Referee::factory()->create(), RefereesTable::class, 'referees.actions.deleted'],
    'stable' => [LifecycleOwnerType::Stable, fn () => Stable::factory()->inactive()->create(), StablesTable::class, 'stables.actions.deleted'],
    'tag team' => [LifecycleOwnerType::TagTeam, fn () => TagTeam::factory()->create(), TagTeamsTable::class, 'tag-teams.actions.deleted'],
    'title' => [LifecycleOwnerType::Title, fn () => Title::factory()->create(), TitlesTable::class, 'titles.actions.deleted'],
    'venue' => [LifecycleOwnerType::Venue, fn () => Venue::factory()->create(), VenuesTable::class, 'venues.actions.deleted'],
    'wrestler' => [LifecycleOwnerType::Wrestler, fn () => Wrestler::factory()->create(), WrestlersTable::class, 'wrestlers.actions.deleted'],
]);
