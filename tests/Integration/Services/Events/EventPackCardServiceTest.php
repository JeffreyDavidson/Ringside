<?php

declare(strict_types=1);

use App\Actions\Events\PrepareLiveShowAction;
use App\Actions\Events\SaveEventPackAction;
use App\Actions\Roster\Availability\CreateAction;
use App\Data\Events\EventPackData;
use App\Data\Roster\UnavailabilityData;
use App\Enums\Matches\MatchFinish;
use App\Enums\Matches\MatchType;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchStipulation;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Services\Events\EventPackCardService;
use App\Services\Events\EventRosterAvailabilityService;
use App\ViewModels\Events\EventPackViewModel;

use function Pest\Laravel\actingAs;

test('calendar restrictions flag an existing card and pack without exposing their internal reasons', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create(['timezone' => 'America/New_York']);
    $event = Event::factory()->for($promotion)->create(['date' => now()->addWeek()]);
    $person = Wrestler::factory()->bookable()->for($promotion)->create();
    $match = EventMatch::factory()->forEvent($event)->withCompetitors([$person])->create();
    $day = ($event->local_date ?? throw new LogicException('Expected a scheduled fixture'))->toDateString();
    resolve(CreateAction::class)->handle($person, new UnavailabilityData($day, $day, 'INTERNAL-AVAILABILITY-ONLY'), administrator());

    // Act
    $card = resolve(EventPackCardService::class)->get($event);

    // Assert
    expect($card['rows'][0]['warning'])->toBeTrue()
        ->and($card['roster'][0]['warning'])->toBeTrue()
        ->and(json_encode($card))->not->toContain('INTERNAL-AVAILABILITY-ONLY')
        ->and($match->competitors()->count())->toBe(1);
});

test('adding unavailability changes review warnings without rewriting a saved pack or prepared live run', function (): void {
    // Arrange
    $actor = administrator();
    actingAs($actor);
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion)->create(['date' => now()]);
    $person = Wrestler::factory()->bookable()->for($promotion)->create();
    EventMatch::factory()->forEvent($event)->withCompetitors([$person])->create(['match_finish' => null]);
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(SaveEventPackAction::class)->handle($event, 0, $data['card']['fingerprint'], EventPackData::fromValidated($data['content']), false);
    $run = resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint'], signedInUser());
    $pack = $event->pack()->firstOrFail();
    $savedPack = $pack->getAttributes();
    $savedRun = $run->refresh()->getAttributes();
    $day = ($event->local_date ?? throw new LogicException('Expected a scheduled fixture'))->toDateString();

    // Act
    resolve(CreateAction::class)->handle($person, new UnavailabilityData($day, $day, 'PRIVATE-HISTORY-REASON'), $actor);
    $card = resolve(EventPackCardService::class)->get($event);

    // Assert
    expect($card['fingerprint'])->not->toBe($data['card']['fingerprint'])
        ->and(json_encode($card))->not->toContain('PRIVATE-HISTORY-REASON')
        ->and($pack->refresh()->getAttributes())->toBe($savedPack)
        ->and($run->refresh()->getAttributes())->toBe($savedRun);
});

test('a completed past card is not retroactively flagged by calendar entries', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion)->create(['date' => now()->subWeek()]);
    $person = Wrestler::factory()->bookable()->for($promotion)->create();
    EventMatch::factory()->forEvent($event)->withCompetitors([$person])->create(['match_finish' => MatchFinish::NoDecision]);
    $day = ($event->local_date ?? throw new LogicException('Expected a scheduled fixture'))->toDateString();
    resolve(CreateAction::class)->handle($person, new UnavailabilityData($day, $day, null), administrator());

    // Act
    $issues = resolve(EventRosterAvailabilityService::class)->get($event);
    $card = resolve(EventPackCardService::class)->get($event);

    // Assert
    expect($issues)->toBeEmpty()->and($card['rows'][0]['warning'])->toBeFalse()
        ->and($card['roster'][0]['warning'])->toBeFalse();
});

test('prints each match and person on the card and fingerprints exactly that card', function (): void {
    // Arrange
    $actor = administrator();
    $promotion = Promotion::factory()
        ->create(['timezone' => 'America/New_York']);
    $venue = Venue::factory()
        ->create(['name' => 'Harbor Hall']);
    $event = Event::factory()
        ->for($promotion)
        ->create(['name' => 'Harbor Showcase', 'date' => now()->addWeek(), 'venue_id' => $venue->id]);
    [$alpha, $bravo] = Wrestler::factory()
        ->count(2)
        ->sequence(['name' => 'Alpha'], ['name' => 'Bravo'])
        ->bookable()
        ->for($promotion)
        ->create()
        ->all();
    $team = TagTeam::factory()
        ->bookable()
        ->for($promotion)
        ->create(['name' => 'Duo']);
    [$echo, $foxtrot] = $team->currentWrestlers()->orderBy('wrestlers.id')->get()->all();
    $echo->forceFill(['name' => 'Echo', 'promotion_id' => $promotion->id])->save();
    $foxtrot->forceFill(['name' => 'Foxtrot', 'promotion_id' => $promotion->id])->save();
    $referee = Referee::factory()
        ->bookable()
        ->for($promotion)
        ->create(['first_name' => 'Ref', 'last_name' => 'One']);
    $title = Title::factory()
        ->create(['name' => 'Harbor Title']);
    $stipulation = MatchStipulation::factory()
        ->create(['name' => 'No DQ']);
    $first = EventMatch::factory()
        ->forEvent($event)
        ->withCompetitors([$alpha, $bravo])
        ->create(['match_stipulation_id' => $stipulation->id]);
    $first->referees()->attach($referee);
    $first->titles()->attach($title);
    $second = EventMatch::factory()
        ->forEvent($event)
        ->withCompetitors([$team, $alpha])
        ->create();
    $day = ($event->local_date ?? throw new LogicException('Expected a scheduled fixture'))->toDateString();
    resolve(CreateAction::class)->handle($bravo, new UnavailabilityData($day, $day, null), $actor);
    $wrestlerRole = __('event-pack.wrestler');
    $expectedRows = [
        ['id' => $first->id, 'number' => 1, 'label' => 'Alpha vs Bravo', 'details' => implode(' · ', [MatchType::Singles->label(), 'No DQ', 'Harbor Title']), 'referees' => 'Ref One', 'warning' => true],
        ['id' => $second->id, 'number' => 2, 'label' => 'Duo vs Alpha', 'details' => MatchType::Singles->label(), 'referees' => '', 'warning' => false],
    ];
    $expectedRoster = [
        ['key' => "wrestler:{$alpha->id}", 'name' => 'Alpha', 'role' => $wrestlerRole, 'matches' => [1, 2], 'warning' => false],
        ['key' => "wrestler:{$bravo->id}", 'name' => 'Bravo', 'role' => $wrestlerRole, 'matches' => [1], 'warning' => true],
        ['key' => "referee:{$referee->id}", 'name' => 'Ref One', 'role' => __('event-pack.referee'), 'matches' => [1], 'warning' => false],
        ['key' => "wrestler:{$echo->id}", 'name' => 'Echo', 'role' => 'Duo', 'matches' => [2], 'warning' => false],
        ['key' => "wrestler:{$foxtrot->id}", 'name' => 'Foxtrot', 'role' => 'Duo', 'matches' => [2], 'warning' => false],
    ];
    // The recipe stored pack fingerprints were made with; a change to it would flag every saved pack for review.
    $expectedFingerprint = hash('sha256', serialize(['Harbor Showcase', $event->date?->toIso8601String(), $venue->id, 'Harbor Hall', 'America/New_York', $expectedRows, $expectedRoster]));

    // Act
    $card = resolve(EventPackCardService::class)->get($event);

    // Assert
    expect($card)->toBe(['fingerprint' => $expectedFingerprint, 'timezone' => 'America/New_York', 'rows' => $expectedRows, 'roster' => $expectedRoster]);
});

test('reads the matches of the card once, however many people stand on it', function (): void {
    // Arrange
    $actor = administrator();
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create(['date' => now()->addWeek()]);
    $team = TagTeam::factory()
        ->bookable()
        ->for($promotion)
        ->create();
    $wrestler = Wrestler::factory()
        ->bookable()
        ->for($promotion)
        ->create();
    EventMatch::factory()
        ->forEvent($event)
        ->withCompetitors([$team, $wrestler])
        ->withReferees()
        ->create();
    $day = ($event->local_date ?? throw new LogicException('Expected a scheduled fixture'))->toDateString();
    resolve(CreateAction::class)->handle($wrestler, new UnavailabilityData($day, $day, null), $actor);
    $service = resolve(EventPackCardService::class);

    // Act
    $card = $service->get($event);
    $statements = recordStatements(fn () => $service->get($event));

    // Assert
    expect($card['rows'][0]['warning'])->toBeTrue()
        ->and(collect($statements)->filter(fn (array $statement): bool => str_contains($statement['sql'], 'from "events_matches" where')))->toHaveCount(1)
        ->and($statements)->toHaveCount(14);
});

test('flags a card when a member of a tag team on it is unavailable', function (): void {
    // Arrange
    $actor = administrator();
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create(['date' => now()->addWeek()]);
    $team = TagTeam::factory()
        ->bookable()
        ->for($promotion)
        ->create();
    $members = $team->currentWrestlers()->orderBy('wrestlers.id')->get();
    $members->each(fn (Wrestler $member) => $member->forceFill(['promotion_id' => $promotion->id])->save());
    $blockedMember = $members->firstOrFail();
    EventMatch::factory()
        ->forEvent($event)
        ->withCompetitors([$team])
        ->create();
    $service = resolve(EventPackCardService::class);
    $clear = $service->get($event);
    $day = ($event->local_date ?? throw new LogicException('Expected a scheduled fixture'))->toDateString();
    resolve(CreateAction::class)->handle($blockedMember, new UnavailabilityData($day, $day, null), $actor);

    // Act
    $blocked = $service->get($event);

    // Assert
    expect($clear['rows'][0]['warning'])->toBeFalse()
        ->and(collect($clear['roster'])->pluck('warning')->all())->toBe([false, false])
        ->and($blocked['rows'][0]['warning'])->toBeTrue()
        ->and(collect($blocked['roster'])->pluck('warning')->all())->toBe([true, false])
        ->and($blocked['fingerprint'])->not->toBe($clear['fingerprint']);
});

test('an event with no matches has an empty card but still a fingerprint', function (): void {
    // Arrange
    $event = Event::factory()
        ->create(['name' => 'Empty Night', 'date' => null, 'venue_id' => null]);
    $expected = hash('sha256', serialize(['Empty Night', null, null, null, 'UTC', [], []]));

    // Act
    $card = resolve(EventPackCardService::class)->get($event);

    // Assert
    expect($card['rows'])->toBeEmpty()
        ->and($card['roster'])->toBeEmpty()
        ->and($card['fingerprint'])->toBe($expected);
});

test('the card fingerprint does not change when the venue is deleted afterwards', function (): void {
    // Arrange
    $venue = Venue::factory()
        ->create(['name' => 'Harbor Hall']);
    $event = Event::factory()
        ->for(Promotion::factory())
        ->create(['date' => now()->addWeek(), 'venue_id' => $venue->id]);
    $before = resolve(EventPackCardService::class)->get($event)['fingerprint'];
    $venue->delete();

    // Act
    $after = resolve(EventPackCardService::class)->get(Event::query()->findOrFail($event->id))['fingerprint'];

    // Assert
    expect($after)->toBe($before);
});
