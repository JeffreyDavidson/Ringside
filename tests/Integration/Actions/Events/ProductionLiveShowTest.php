<?php

declare(strict_types=1);

use App\Actions\Events\CompleteLiveShowAction;
use App\Actions\Events\MarkLiveAttendanceAction;
use App\Actions\Events\PrepareLiveShowAction;
use App\Actions\Events\ReviewLiveShowAction;
use App\Actions\Events\SaveEventProductionAction;
use App\Actions\Events\TransitionLiveItemAction;
use App\Data\Events\EventProductionData;
use App\Data\Events\LiveShowReviewData;
use App\Enums\Events\LiveAttendanceMark;
use App\Enums\Events\LiveItemOperation;
use App\Exceptions\Events\LiveShowException;
use App\Models\Crew\CrewContact;
use App\ViewModels\Events\EventPackViewModel;
use Spatie\Activitylog\Models\Activity;

require_once __DIR__.'/../../../Fixtures/production.php';

test('preparation snapshots crew and manual cues without copying private contact notes', function (): void {
    // Arrange
    $fixture = productionFixture();
    $event = $fixture['event'];
    $data = resolve(EventPackViewModel::class)->data($event);

    // Act
    $run = resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint']);

    // Assert
    expect(collect($run->content['roster'])->where('key', 'crew:'.$fixture['contact']->id))->toHaveCount(1)
        ->and(data_get($run->content, 'items.0.cues.0.instruction'))->toBe('PRIVATE-CUE-7Q')
        ->and(json_encode($run->content))->not->toContain('PRIVATE-NOTE-7Q');
    resolve(MarkLiveAttendanceAction::class)->handle($event, 1, 'crew:'.$fixture['contact']->id, LiveAttendanceMark::Arrived, true);
    expect(data_get(collect($run->refresh()->content['roster'])->firstWhere('key', 'crew:'.$fixture['contact']->id), 'arrived'))->not->toBeNull();
});

test('source adoption preserves started cue owners and removed crew attendance', function (): void {
    // Arrange
    $fixture = productionFixture();
    $event = $fixture['event'];
    $data = resolve(EventPackViewModel::class)->data($event);
    $run = resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint']);
    resolve(MarkLiveAttendanceAction::class)->handle($event, 1, 'crew:'.$fixture['contact']->id, LiveAttendanceMark::Arrived, true);
    resolve(TransitionLiveItemAction::class)->handle($event, 2, 'match:'.$fixture['match']->id, LiveItemOperation::Start);
    $production = $fixture['production'];
    $production['assignments'] = [];
    $production['cues'][0]['instruction'] = 'Changed pending instructions';
    resolve(SaveEventProductionAction::class)->handle($event, 1, $data['card']['fingerprint'], EventProductionData::fromValidated($production), false);

    // Act
    resolve(ReviewLiveShowAction::class)->handle($event, 3, 2, $data['card']['fingerprint'], LiveShowReviewData::fromValidated([]));

    // Assert
    $content = $run->refresh()->content;
    expect(data_get($content, 'items.0.cues.0.instruction'))->toBe('PRIVATE-CUE-7Q')
        ->and(data_get($content, 'items.0.cues.0.owner_name'))->toBe('PRIVATE-CREW-7Q')
        ->and(collect($content['roster'])->firstWhere('key', 'crew:'.$fixture['contact']->id))->toMatchArray(['assigned' => false])
        ->and(data_get(collect($content['roster'])->firstWhere('key', 'crew:'.$fixture['contact']->id), 'arrived'))->not->toBeNull();
});

test('live operation audit properties exclude private crew names and instructions', function (): void {
    // Arrange
    $fixture = productionFixture();
    $event = $fixture['event'];
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint']);

    // Act
    resolve(TransitionLiveItemAction::class)->handle($event, 1, 'match:'.$fixture['match']->id, LiveItemOperation::Start);

    // Assert
    expect(Activity::query()->where('log_name', 'live-show')->get()->pluck('properties')->toJson())
        ->not->toContain('PRIVATE-CUE-7Q', 'PRIVATE-CREW-7Q');
});

test('a crew attendance mark audits only the attendance fields', function (): void {
    // Arrange
    $fixture = productionFixture();
    $event = $fixture['event'];
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint']);

    // Act
    resolve(MarkLiveAttendanceAction::class)->handle($event, 1, 'crew:'.$fixture['contact']->id, LiveAttendanceMark::Arrived, true);

    // Assert
    $audit = Activity::query()->where('log_name', 'live-show')->orderByDesc('id')->firstOrFail();
    expect(array_keys($audit->properties['before']))->toEqualCanonicalizing(['arrived', 'briefed', 'updated_by'])
        ->and(array_keys($audit->properties['after']))->toEqualCanonicalizing(['arrived', 'briefed', 'updated_by'])
        ->and($audit->properties['before']['arrived'])->toBeNull()
        ->and($audit->properties['after']['arrived'])->not->toBeNull();
});

test('several production roles create one crew attendance entry and completion freezes history', function (): void {
    // Arrange
    $fixture = productionFixture();
    $event = $fixture['event'];
    $data = resolve(EventPackViewModel::class)->data($event);
    $input = $fixture['production'];
    $input['assignments'][] = [...$input['assignments'][0], 'id' => 'eed36a82-90cb-4a79-b2cc-92e6c4051002', 'role' => 'announcer'];
    resolve(SaveEventProductionAction::class)->handle($event, 1, $data['card']['fingerprint'], EventProductionData::fromValidated($input), false);
    $run = resolve(PrepareLiveShowAction::class)->handle($event, 0, 2, $data['card']['fingerprint']);

    // Act / Assert
    expect(collect($run->content['roster'])->where('key', 'crew:'.$fixture['contact']->id))->toHaveCount(1);
    resolve(TransitionLiveItemAction::class)->handle($event, 1, 'match:'.$fixture['match']->id, LiveItemOperation::Start);
    resolve(TransitionLiveItemAction::class)->handle($event, 2, 'match:'.$fixture['match']->id, LiveItemOperation::Finish);
    resolve(CompleteLiveShowAction::class)->handle($event, 3);
    $before = $run->refresh()->content;
    $input['cues'][0]['instruction'] = 'Later plan correction';
    resolve(SaveEventProductionAction::class)->handle($event, 2, $data['card']['fingerprint'], EventProductionData::fromValidated($input), false);
    expect($run->refresh()->content)->toBe($before)
        ->and(fn () => resolve(MarkLiveAttendanceAction::class)->handle($event, 4, 'crew:'.$fixture['contact']->id, LiveAttendanceMark::Arrived, true))->toThrow(LiveShowException::class);
});

test('preparation rolls each crew contact into one roster entry with its distinct role labels in the order first seen', function (): void {
    // Arrange
    $fixture = productionFixture();
    $event = $fixture['event'];
    $other = CrewContact::factory()->create(['promotion_id' => $event->promotion_id, 'name' => 'Second Operator']);
    $first = ['contact_id' => $fixture['contact']->id, 'timezone' => 'UTC'];
    $assignments = [
        [...$first, 'id' => 'eed36a82-90cb-4a79-b2cc-92e6c4052001', 'role' => 'camera'],
        ['id' => 'eed36a82-90cb-4a79-b2cc-92e6c4052002', 'contact_id' => $other->id, 'role' => 'sound', 'timezone' => 'UTC'],
        [...$first, 'id' => 'eed36a82-90cb-4a79-b2cc-92e6c4052003', 'role' => 'announcer'],
        [...$first, 'id' => 'eed36a82-90cb-4a79-b2cc-92e6c4052004', 'role' => 'camera'],
    ];
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(SaveEventProductionAction::class)->handle($event, 1, $data['card']['fingerprint'], EventProductionData::fromValidated(['notes' => null, 'assignments' => $assignments, 'cues' => []]), false);

    // Act
    $run = resolve(PrepareLiveShowAction::class)->handle($event, 0, 2, $data['card']['fingerprint']);

    // Assert
    $crew = collect($run->content['roster'])->filter(fn (array $person): bool => str_starts_with($person['key'], 'crew:'))->values();
    expect($crew->pluck('key')->all())->toBe(['crew:'.$fixture['contact']->id, 'crew:'.$other->id])
        ->and($crew->pluck('role')->all())->toBe(['Camera, Announcer', 'Sound'])
        ->and(data_get($crew->firstOrFail(), 'assignments'))->toHaveCount(3)
        ->and($crew->firstOrFail()['name'])->toBe('PRIVATE-CREW-7Q');
});
