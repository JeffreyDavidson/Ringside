<?php

declare(strict_types=1);

use App\Actions\Events\PrepareLiveShowAction;
use App\Actions\Events\SaveEventProductionAction;
use App\Models\Crew\CrewContact;
use App\Models\Events\Event;
use App\Models\Events\EventLiveRun;
use App\Models\Events\EventPack;
use App\ViewModels\EventPackViewModel;

use function Pest\Laravel\get;

require_once __DIR__.'/../../../../Fixtures/production.php';

test('production print defaults to the live snapshot and excludes private notes', function (): void {
    // Arrange
    $fixture = productionFixture();
    $event = $fixture['event'];
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint']);
    $production = $fixture['production'];
    $production['cues'][0]['instruction'] = 'UNADOPTED-CUE-7Q';
    resolve(SaveEventProductionAction::class)->handle($event, 1, $data['card']['fingerprint'], $production, false);

    // Act
    $live = get(route('events.production-sheet.show', ['event' => $event, 'print' => 1]));
    $plan = get(route('events.production-sheet.show', ['event' => $event, 'print' => 1, 'source' => 'plan']));

    // Assert
    $live->assertOk()->assertSee('PRIVATE-CUE-7Q')->assertDontSee('UNADOPTED-CUE-7Q')->assertDontSee('PRIVATE-NOTE-7Q');
    $plan->assertOk()->assertSee('UNADOPTED-CUE-7Q')->assertDontSee('PRIVATE-NOTE-7Q');
});

test('long cue sheets retain full manual instructions and never print contact details', function (): void {
    // Arrange
    $fixture = productionFixture();
    $event = $fixture['event'];
    $fixture['contact']->phone = 'CONTACT-PHONE-7Q';
    $fixture['contact']->notes = 'CONTACT-NOTE-7Q';
    $fixture['contact']->save();
    $input = $fixture['production'];
    $input['cues'] = array_map(fn (int $i): array => [...$input['cues'][0], 'id' => sprintf('eed36a82-90cb-4a79-b2cc-%012d', $i),
        'subject' => 'Long cue '.$i, 'instruction' => str_repeat('Hold the entrance until the caller gives the signal. Fade under the announcement and keep the next entrance ready. ', 12)], range(1, 6));
    resolve(SaveEventProductionAction::class)->handle($event, 1, $event->pack()->firstOrFail()->card_fingerprint, $input, false);

    // Act
    $response = get(route('events.production-sheet.show', ['event' => $event, 'print' => 1]));

    // Assert
    $response->assertOk()->assertSee('Long cue 6')->assertDontSee('CONTACT-PHONE-7Q')->assertDontSee('CONTACT-NOTE-7Q');
});

test('opening an empty production sheet creates no data and labels the plan unsaved', function (): void {
    // Arrange
    \Pest\Laravel\actingAs(administrator());
    $event = Event::factory()->create();

    // Act
    $response = get(route('events.production-sheet.show', $event));

    // Assert
    $response->assertOk()->assertSee('Not saved');
    expect(EventPack::query()->count())->toBe(0)
        ->and(EventLiveRun::query()->count())->toBe(0)
        ->and(CrewContact::query()->count())->toBe(0);
});
