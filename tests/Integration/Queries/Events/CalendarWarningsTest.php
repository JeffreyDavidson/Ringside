<?php

declare(strict_types=1);

use App\Actions\Events\PrepareLiveShowAction;
use App\Actions\Events\SaveEventPackAction;
use App\Actions\Roster\Availability\CreateAction;
use App\Enums\Matches\MatchFinish;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Events\EventPackCardQuery;
use App\Queries\Events\EventRosterAvailabilityQuery;
use App\ViewModels\EventPackViewModel;

use function Pest\Laravel\actingAs;

test('calendar restrictions flag an existing card and pack without exposing their internal reasons', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create(['timezone' => 'America/New_York']);
    $event = Event::factory()->for($promotion)->create(['date' => now()->addWeek()]);
    $person = Wrestler::factory()->bookable()->for($promotion)->create();
    $match = EventMatch::factory()->forEvent($event)->withCompetitors([$person])->create();
    $day = ($event->local_date ?? throw new LogicException('Expected a scheduled fixture'))->toDateString();
    resolve(CreateAction::class)->handle($person, $day, $day, 'INTERNAL-AVAILABILITY-ONLY', administrator());

    // Act
    $card = resolve(EventPackCardQuery::class)->get($event);

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
    resolve(SaveEventPackAction::class)->handle($event, 0, $data['card']['fingerprint'], $data['content'], false);
    $run = resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint']);
    $pack = $event->pack()->firstOrFail();
    $savedPack = $pack->getAttributes();
    $savedRun = $run->refresh()->getAttributes();
    $day = ($event->local_date ?? throw new LogicException('Expected a scheduled fixture'))->toDateString();

    // Act
    resolve(CreateAction::class)->handle($person, $day, $day, 'PRIVATE-HISTORY-REASON', $actor);
    $card = resolve(EventPackCardQuery::class)->get($event);

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
    resolve(CreateAction::class)->handle($person, $day, $day, null, administrator());

    // Act
    $issues = resolve(EventRosterAvailabilityQuery::class)->unavailable($event);
    $card = resolve(EventPackCardQuery::class)->get($event);

    // Assert
    expect($issues)->toBeEmpty()->and($card['rows'][0]['warning'])->toBeFalse()
        ->and($card['roster'][0]['warning'])->toBeFalse();
});
