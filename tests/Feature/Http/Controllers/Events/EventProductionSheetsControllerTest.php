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
    $response = get(route('events.production-sheet.show', ['event' => $event, 'print' => 1]));
    $planResponse = get(route('events.production-sheet.show', ['event' => $event, 'print' => 1, 'source' => 'plan']));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.production-print')
        ->assertViewHas('sheet', fn (array $sheet): bool => $sheet['event']->is($event) && $sheet['source'] === 'live')
        ->assertSee('PRIVATE-CUE-7Q')
        ->assertDontSee('UNADOPTED-CUE-7Q')
        ->assertDontSee('PRIVATE-NOTE-7Q');
    $planResponse
        ->assertOk()
        ->assertViewIs('events.production-print')
        ->assertViewHas('sheet', fn (array $sheet): bool => $sheet['event']->is($event) && $sheet['source'] === 'plan')
        ->assertSee('UNADOPTED-CUE-7Q')
        ->assertDontSee('PRIVATE-NOTE-7Q');
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
    $response
        ->assertOk()
        ->assertViewIs('events.production-print')
        ->assertSee($input['cues'][5]['instruction'])
        ->assertSee('Long cue 6')
        ->assertDontSee('CONTACT-PHONE-7Q')
        ->assertDontSee('CONTACT-NOTE-7Q');
});

test('opening an empty production sheet creates no data and labels the plan unsaved', function (): void {
    // Arrange
    \Pest\Laravel\actingAs(administrator());
    $event = Event::factory()->create();

    // Act
    $response = get(route('events.production-sheet.show', $event));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.production')
        ->assertViewHas('sheet', fn (array $sheet): bool => $sheet['event']->is($event) && $sheet['source'] === 'plan' && $sheet['version'] === 0)
        ->assertSee('Not saved');

    expect(EventPack::query()->count())->toBe(0)
        ->and(EventLiveRun::query()->count())->toBe(0)
        ->and(CrewContact::query()->count())->toBe(0);
});

test('production requests apply person and role filters to the sheet payload', function (string $filter, bool $included): void {
    // Arrange
    $fixture = productionFixture();
    $event = $fixture['event'];
    $parameters = match ($filter) {
        'person' => ['person' => $fixture['contact']->id],
        'other person' => ['person' => 999999],
        'role' => ['role' => 'sound'],
        'other role' => ['role' => 'announcer'],
        default => throw new LogicException('Unknown production filter case'),
    };

    // Act
    $response = get(route('events.production-sheet.show', ['event' => $event, ...$parameters]));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.production');

    $sheet = $response->viewData('sheet');
    expect(data_get($sheet, 'event.id'))->toBe($event->id)
        ->and(data_get($sheet, 'assignments.*.contact_id'))->toBe($included ? [$fixture['contact']->id] : [])
        ->and(data_get($sheet, 'person'))->toBe($parameters['person'] ?? null)
        ->and(data_get($sheet, 'role'))->toBe($parameters['role'] ?? null)
        ->and(data_get($sheet, 'rows.0.cues'))->toHaveCount($included ? 1 : 0);
})->with(['person' => ['person', true], 'other person' => ['other person', false], 'role' => ['role', true], 'other role' => ['other role', false]]);

test('invalid production filters return validation errors', function (string $field, mixed $value): void {
    // Arrange
    \Pest\Laravel\actingAs(administrator());
    $event = Event::factory()->create();

    // Act
    $response = get(route('events.production-sheet.show', ['event' => $event, $field => $value]));

    // Assert
    $response
        ->assertRedirect()
        ->assertSessionHasErrors($field);

    expect(EventPack::query()->count())->toBe(0);
})->with(['source' => ['source', 'invalid'], 'person' => ['person', 'bad'], 'role' => ['role', 'invalid'], 'print' => ['print', 'bad']]);

test('guests cannot open production sheets', function (): void {
    // Arrange
    $event = Event::factory()->create();

    // Act
    $response = get(route('events.production-sheet.show', $event));

    // Assert
    $response->assertRedirect(route('login'));
});
