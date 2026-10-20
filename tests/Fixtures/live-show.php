<?php

declare(strict_types=1);

use App\Actions\Events\PrepareLiveShowAction;
use App\Actions\Events\SaveEventPackAction;
use App\Data\Events\EventPackData;
use App\Models\Events\Event;
use App\Models\Events\EventLiveRun;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Wrestlers\Wrestler;
use App\ViewModels\Events\EventPackViewModel;

use function Pest\Laravel\actingAs;

/** @return array{event: Event, run: EventLiveRun, key: string, person: string} */
function liveShowFixture(bool $segment = false, ?string $date = null, ?int $venueId = null): array
{
    actingAs(administrator());
    $event = Event::factory()->create([
        'name' => 'Harbor Showcase',
        'date' => $date ?? now(),
        ...($venueId === null ? [] : ['venue_id' => $venueId]),
    ]);
    $wrestlers = Wrestler::factory()->count(2)->sequence(
        ['name' => 'Prof. Russ Kihn'],
        ['name' => 'Yasmine Nolan'],
    )->bookable()->create();
    $match = EventMatch::factory()->for($event)->withCompetitors($wrestlers->all())->withReferees()->create();
    $data = resolve(EventPackViewModel::class)->data($event);
    $content = $data['content'];
    if ($segment) {
        $content['segments'] = [['id' => '1f64fc86-5bbf-4a41-952d-b85800358f00', 'label' => 'Closing promo', 'after_match_id' => $match->id,
            'planned_at' => null, 'revised_at' => null, 'minutes' => 10, 'notes' => null]];
    }
    resolve(SaveEventPackAction::class)->handle($event, 0, $data['card']['fingerprint'], EventPackData::fromValidated($content), false);
    $run = resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint'])->refresh();

    return ['event' => $event, 'run' => $run, 'key' => $run->content['items'][0]['key'], 'person' => $run->content['roster'][0]['key']];
}

/**
 * Overwrite fields of the run's items in memory, by position, so a transition can be applied to any item state.
 *
 * @param  array<int, array<string, mixed>>  $items
 */
function withLiveItems(EventLiveRun $run, array $items): EventLiveRun
{
    $content = $run->content;
    foreach ($items as $position => $fields) {
        $content['items'][$position] = [...$content['items'][$position], ...$fields];
    }
    $run->content = $content;

    return $run;
}
