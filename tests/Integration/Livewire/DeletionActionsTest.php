<?php

declare(strict_types=1);

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleOwnerType;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Livewire\Events\Tables\Main as EventsTable;
use App\Livewire\Managers\Tables\Main as ManagersTable;
use App\Livewire\Matches\Tables\Main as MatchesTable;
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

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

test('table deletions use the typed lifecycle action', function (LifecycleOwnerType $ownerType) {
    $owner = match ($ownerType) {
        LifecycleOwnerType::Event => Event::factory()->create(),
        LifecycleOwnerType::Manager => Manager::factory()->create(),
        LifecycleOwnerType::Match => EventMatch::factory()->create(),
        LifecycleOwnerType::Referee => Referee::factory()->create(),
        LifecycleOwnerType::Stable => Stable::factory()->inactive()->create(),
        LifecycleOwnerType::TagTeam => TagTeam::factory()->create(),
        LifecycleOwnerType::Title => Title::factory()->create(),
        LifecycleOwnerType::Venue => Venue::factory()->create(),
        LifecycleOwnerType::Wrestler => Wrestler::factory()->create(),
    };
    $component = match ($ownerType) {
        LifecycleOwnerType::Event => EventsTable::class,
        LifecycleOwnerType::Manager => ManagersTable::class,
        LifecycleOwnerType::Match => MatchesTable::class,
        LifecycleOwnerType::Referee => RefereesTable::class,
        LifecycleOwnerType::Stable => StablesTable::class,
        LifecycleOwnerType::TagTeam => TagTeamsTable::class,
        LifecycleOwnerType::Title => TitlesTable::class,
        LifecycleOwnerType::Venue => VenuesTable::class,
        LifecycleOwnerType::Wrestler => WrestlersTable::class,
    };

    actingAs(administrator());

    livewire($component)
        ->call('delete', $owner)
        ->assertHasNoErrors();

    $transition = LifecycleTransition::query()
        ->where('subject_type', $ownerType->morphAlias())
        ->where('subject_id', $owner->getKey())
        ->sole();
    $owner->refresh();

    expect($owner->trashed())->toBeTrue()
        ->and($transition->dimension)->toBe(LifecycleDimension::Deletion)
        ->and($transition->transition)->toBe(LifecycleTransitionType::Deleted);
})->with(LifecycleOwnerType::cases());
