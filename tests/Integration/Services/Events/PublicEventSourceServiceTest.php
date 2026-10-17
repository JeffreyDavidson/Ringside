<?php

declare(strict_types=1);

use App\Enums\Matches\MatchFinish;
use App\Enums\Promotions\MembershipRole;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchStipulation;
use App\Models\Promotions\Promotion;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Services\Events\PublicEventSourceService;

/**
 * @return array{event: Event, promotion: Promotion, venue: Venue}
 */
function publicSourceWorld(): array
{
    $promotion = Promotion::factory()
        ->create(['name' => 'Harbor Wrestling', 'timezone' => 'America/New_York']);
    $venue = Venue::factory()
        ->create(['name' => 'Harbor Arena', 'street_address' => '1 Dock Road', 'city' => 'Portland', 'state' => 'ME', 'zipcode' => '04101', 'timezone' => 'America/Chicago']);
    $event = Event::factory()
        ->for($promotion)
        ->create(['name' => 'Harbor Night', 'date' => '2026-10-17 19:00:00', 'venue_id' => $venue->id]);

    return ['event' => $event, 'promotion' => $promotion, 'venue' => $venue];
}

describe('public event source service', function (): void {
    it('describes the event with matches, labels and fingerprints that can be recomputed from the printed values', function (): void {
        // Arrange
        ['event' => $event, 'promotion' => $promotion, 'venue' => $venue] = publicSourceWorld();
        $robin = Wrestler::factory()->bookable()->for($promotion)->create(['name' => 'Robin North']);
        $casey = Wrestler::factory()->bookable()->for($promotion)->create(['name' => 'Casey Shore']);
        $team = TagTeam::factory()->for($promotion)->create(['name' => 'The Dockhands']);
        $title = Title::factory()->create(['name' => 'World Title', 'promotion_id' => $promotion->id]);
        $stipulation = MatchStipulation::factory()->create(['name' => 'Cage Match']);
        $first = EventMatch::factory()
            ->for($event)
            ->withCompetitors([$robin, $casey])
            ->create(['match_stipulation_id' => $stipulation->id]);
        $first->titles()->attach($title);
        $first->forceFill(['match_finish' => MatchFinish::Pinfall, 'winning_side_id' => $first->sides()->orderBy('position')->firstOrFail()->id])->save();
        $second = EventMatch::factory()
            ->for($event)
            ->withCompetitors([$team, $robin])
            ->create();

        // Act
        $source = resolve(PublicEventSourceService::class)->get($event);

        // Assert
        $matches = [
            ['id' => $first->id, 'label' => 'Robin North vs Casey Shore', 'type' => 'Singles', 'stipulation' => 'Cage Match', 'titles' => 'World Title', 'finish' => 'Pinfall', 'winner' => 'Robin North'],
            ['id' => $second->id, 'label' => 'The Dockhands vs Robin North', 'type' => 'Singles', 'stipulation' => null, 'titles' => '', 'finish' => null, 'winner' => null],
        ];
        $data = ['title' => 'Harbor Night', 'promotion' => 'Harbor Wrestling', 'date' => '2026-10-17T19:00:00+00:00', 'timezone' => 'America/Chicago',
            'venue' => 'Harbor Arena', 'address' => '1 Dock Road, Portland, ME, 04101', 'matches' => $matches];
        expect($source)->toBe([...$data,
            'fingerprint' => hash('sha256', serialize($data)),
            'schedule' => hash('sha256', serialize(['2026-10-17T19:00:00+00:00', $venue->id, 'Harbor Arena', $data['timezone'], '1 Dock Road, Portland, ME, 04101']))]);
    });

    it('leaves a match unlabelled when a side has nobody on it or a competitor belongs to another promotion', function (): void {
        // Arrange
        ['event' => $event, 'promotion' => $promotion] = publicSourceWorld();
        $own = Wrestler::factory()->bookable()->for($promotion)->create(['name' => 'Robin North']);
        $foreign = Wrestler::factory()->bookable()->for(Promotion::factory())->create(['name' => 'Casey Shore']);
        $foreignMatch = EventMatch::factory()->for($event)->withCompetitors([$own, $foreign])->create();
        $oneSided = EventMatch::factory()->for($event)->withCompetitors([$own])->create();

        // Act
        $source = resolve(PublicEventSourceService::class)->get($event);

        // Assert
        expect(array_column($source['matches'], 'label', 'id'))->toBe([$foreignMatch->id => '', $oneSided->id => ''])
            ->and(json_encode($source))->not->toContain('Casey Shore');
    });

    it('leaves out the titles of another promotion', function (): void {
        // Arrange
        ['event' => $event, 'promotion' => $promotion] = publicSourceWorld();
        $match = EventMatch::factory()->for($event)->create();
        $match->titles()->attach([
            Title::factory()->create(['name' => 'Own Title', 'promotion_id' => $promotion->id])->id,
            Title::factory()->create(['name' => 'Foreign Title', 'promotion_id' => Promotion::factory()->create()->id])->id,
        ]);

        // Act
        $source = resolve(PublicEventSourceService::class)->get($event);

        // Assert
        expect($source['matches'][0]['titles'])->toBe('Own Title');
    });

    it('names a winner only for finishes that need one and an empty winner when the side is unrecorded', function (): void {
        // Arrange
        ['event' => $event, 'promotion' => $promotion] = publicSourceWorld();
        $wrestlers = Wrestler::factory()->bookable()->for($promotion)->count(2)->create();
        $match = EventMatch::factory()->for($event)->withCompetitors($wrestlers->all())->create();
        $match->forceFill(['match_finish' => MatchFinish::Pinfall, 'winning_side_id' => null])->save();

        // Act
        $source = resolve(PublicEventSourceService::class)->get($event);

        // Assert
        expect($source['matches'][0]['winner'])->toBe('')
            ->and($source['matches'][0]['finish'])->toBe('Pinfall');
    });

    it('describes an unscheduled event without a venue', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create(['name' => 'Harbor Wrestling', 'timezone' => 'America/New_York']);
        $event = Event::factory()->for($promotion)->unscheduled()->create(['name' => 'TBD Night', 'venue_id' => null]);

        // Act
        $source = resolve(PublicEventSourceService::class)->get($event);

        // Assert
        $data = ['title' => 'TBD Night', 'promotion' => 'Harbor Wrestling', 'date' => null, 'timezone' => 'America/New_York', 'venue' => null, 'address' => null, 'matches' => []];
        expect($source)->toBe([...$data,
            'fingerprint' => hash('sha256', serialize($data)),
            'schedule' => hash('sha256', serialize([null, null, null, 'America/New_York', null]))]);
    });

    it('reads the same source for a signed-in visitor from another promotion', function (): void {
        // Arrange
        ['event' => $event, 'promotion' => $promotion] = publicSourceWorld();
        $wrestlers = Wrestler::factory()->bookable()->for($promotion)->count(2)->create();
        EventMatch::factory()->for($event)->withCompetitors($wrestlers->all())->create();
        $before = resolve(PublicEventSourceService::class)->get($event);
        actingAsPromotionMember(Promotion::factory()->create(), MembershipRole::Owner);

        // Act
        $source = resolve(PublicEventSourceService::class)->get($event->refresh());

        // Assert
        expect($source)->toBe($before)
            ->and($source['matches'][0]['label'])->not->toBeEmpty();
    });

    it('reads no more statements for more matches', function (): void {
        // Arrange
        ['event' => $event, 'promotion' => $promotion] = publicSourceWorld();
        $service = resolve(PublicEventSourceService::class);
        $wrestlers = Wrestler::factory()->bookable()->for($promotion)->count(6)->create();
        EventMatch::factory()->for($event)->withCompetitors([$wrestlers[0], $wrestlers[1]])->create();
        $one = recordStatements(fn () => $service->get($event->refresh()));
        EventMatch::factory()->for($event)->withCompetitors([$wrestlers[2], $wrestlers[3]])->create();
        EventMatch::factory()->for($event)->withCompetitors([TagTeam::factory()->for($promotion)->create(), $wrestlers[4]])->create();

        // Act
        $many = recordStatements(fn () => $service->get($event->refresh()));

        // Assert
        expect($many)->toHaveCount(count($one) + 1);
    });
});
