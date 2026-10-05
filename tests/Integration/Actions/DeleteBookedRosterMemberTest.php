<?php

declare(strict_types=1);

use App\Actions\Referees\DeleteAction as DeleteRefereeAction;
use App\Actions\TagTeams\DeleteAction as DeleteTagTeamAction;
use App\Actions\Wrestlers\DeleteAction as DeleteWrestlerAction;
use App\Enums\MatchFinish;
use App\Exceptions\Roster\Individuals\CannotBeDeletedException as IndividualCannotBeDeletedException;
use App\Exceptions\Roster\TagTeams\CannotBeDeletedException as TagTeamCannotBeDeletedException;
use App\Livewire\Matches\Modals\ResultModal;
use App\Livewire\Matches\Tables\MatchesTable;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('deleting a booked wrestler', function (): void {
    it('is rejected while the wrestler is booked on an upcoming card', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->create();
        EventMatch::factory()
            ->forEvent(Event::factory()->scheduled()->create())
            ->withCompetitors([$wrestler, Wrestler::factory()->create()])
            ->create();

        // Act & Assert
        expect(fn () => resolve(DeleteWrestlerAction::class)->handle($wrestler))
            ->toThrow(IndividualCannotBeDeletedException::class);
        expect($wrestler->refresh()->trashed())->toBeFalse();
    });

    it('is allowed after the match result is recorded and keeps the history rendering', function (): void {
        // Arrange
        actingAs(administrator());
        $wrestler = Wrestler::factory()->create(['name' => 'Retired Legend']);
        $event = Event::factory()->past()->create();
        $match = EventMatch::factory()
            ->forEvent($event)
            ->withCompetitors([$wrestler, Wrestler::factory()->create()])
            ->create(['match_finish' => MatchFinish::Pinfall]);

        // Act
        resolve(DeleteWrestlerAction::class)->handle($wrestler);

        // Assert
        livewire(MatchesTable::class, ['eventId' => $event->id])
            ->assertSuccessful()
            ->assertSee('Retired Legend')
            ->assertDontSeeHtml(route('wrestlers.show', $wrestler));
        livewire(ResultModal::class, ['matchId' => $match->id])
            ->assertSuccessful()
            ->assertSee('Retired Legend');
    });
});

describe('deleting a booked referee', function (): void {
    it('is rejected while the referee is booked on an upcoming card', function (): void {
        // Arrange
        $referee = Referee::factory()->create(['first_name' => 'Earl', 'last_name' => 'Hebner']);
        EventMatch::factory()
            ->forEvent(Event::factory()->scheduled()->create())
            ->create()
            ->referees()
            ->attach($referee);

        // Act
        $delete = fn () => resolve(DeleteRefereeAction::class)->handle($referee);

        // Assert
        expect($delete)->toThrow(
            IndividualCannotBeDeletedException::class,
            'cannot be deleted because it is booked in a match that is upcoming or has no result. Remove it from the match or record the result first.',
        )
            ->and($referee->refresh()->trashed())->toBeFalse();
    });
});

describe('deleting a booked tag team', function (): void {
    it('is rejected while the tag team is booked on an upcoming card', function (): void {
        // Arrange
        $tagTeam = TagTeam::factory()->unemployed()->create();
        EventMatch::factory()
            ->forEvent(Event::factory()->scheduled()->create())
            ->withCompetitors([$tagTeam, TagTeam::factory()->create()])
            ->create();

        // Act & Assert
        expect(fn () => resolve(DeleteTagTeamAction::class)->handle($tagTeam))
            ->toThrow(TagTeamCannotBeDeletedException::class);
        expect($tagTeam->refresh()->trashed())->toBeFalse();
    });

    it('is allowed after the match result is recorded and keeps the history rendering', function (): void {
        // Arrange
        actingAs(administrator());
        $tagTeam = TagTeam::factory()->unemployed()->create(['name' => 'Gone Tag Team']);
        $event = Event::factory()->past()->create();
        $match = EventMatch::factory()
            ->forEvent($event)
            ->withCompetitors([$tagTeam, TagTeam::factory()->create()])
            ->create(['match_finish' => MatchFinish::Pinfall]);

        // Act
        resolve(DeleteTagTeamAction::class)->handle($tagTeam);

        // Assert
        livewire(MatchesTable::class, ['eventId' => $event->id])
            ->assertSuccessful()
            ->assertSee('Gone Tag Team')
            ->assertDontSeeHtml(route('tag-teams.show', $tagTeam));
        livewire(ResultModal::class, ['matchId' => $match->id])
            ->assertSuccessful()
            ->assertSee('Gone Tag Team');
    });
});
