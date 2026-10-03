<?php

declare(strict_types=1);

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleOwnerType;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Promotions\MembershipRole;
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
use App\Models\Promotions\Promotion;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Model;
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

/**
 * Whether the record survived a refused deletion: not trashed and no deletion transition recorded for it.
 */
function survivedDeletion(Model $owner): bool
{
    $deletionRecorded = LifecycleTransition::query()
        ->where('subject_type', $owner->getMorphClass())
        ->where('subject_id', $owner->getKey())
        ->where('dimension', LifecycleDimension::Deletion)
        ->exists();

    return freshModel($owner)->getAttribute('deleted_at') === null && ! $deletionRecorded;
}

describe('table deletions without delete permission', function (): void {
    test('a member who may only view cannot delete :dataset from the index table', function (string $component, Closure $createOwner): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $owner = $createOwner($promotion);
        actingAsPromotionMember($promotion, MembershipRole::Member);
        $table = livewire($component);

        // Act
        $table->call('delete', $owner->getKey());

        // Assert
        $table->assertForbidden();

        expect(survivedDeletion($owner))->toBeTrue();
    })->with('promotion-owned index tables');

    test('an owner of another promotion cannot reach :dataset through the index table', function (string $component, Closure $createOwner): void {
        // Arrange
        $owner = $createOwner(Promotion::factory()->create());
        actingAsPromotionMember(Promotion::factory()->create(), MembershipRole::Owner);
        $table = livewire($component);

        // Act
        $table->call('delete', $owner->getKey());

        // Assert
        $table->assertNotFound();

        expect(survivedDeletion($owner))->toBeTrue();
    })->with('promotion-owned index tables');

    test('a promotion owner without venue access cannot delete a venue from a table an administrator loaded', function (): void {
        // Arrange
        $venue = Venue::factory()->create();
        actingAs(administrator());
        $table = livewire(VenuesTable::class);
        actingAsPromotionMember(Promotion::factory()->create(), MembershipRole::Owner);

        // Act
        $table->call('delete', $venue->getKey());

        // Assert
        $table->assertForbidden();

        expect(survivedDeletion($venue))->toBeTrue();
    });
});

dataset('promotion-owned index tables', [
    'an event' => [EventsTable::class, fn (Promotion $promotion): Event => Event::factory()->for($promotion, 'promotion')->create()],
    'a manager' => [ManagersTable::class, fn (Promotion $promotion): Manager => Manager::factory()->for($promotion, 'promotion')->create()],
    'a referee' => [RefereesTable::class, fn (Promotion $promotion): Referee => Referee::factory()->for($promotion, 'promotion')->create()],
    'a stable' => [StablesTable::class, fn (Promotion $promotion): Stable => Stable::factory()->for($promotion, 'promotion')->inactive()->create()],
    'a tag team' => [TagTeamsTable::class, fn (Promotion $promotion): TagTeam => TagTeam::factory()->for($promotion, 'promotion')->create()],
    'a title' => [TitlesTable::class, fn (Promotion $promotion): Title => Title::factory()->for($promotion, 'promotion')->create()],
    'a wrestler' => [WrestlersTable::class, fn (Promotion $promotion): Wrestler => Wrestler::factory()->for($promotion, 'promotion')->create()],
]);
