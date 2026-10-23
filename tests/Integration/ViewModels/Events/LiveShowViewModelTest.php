<?php

declare(strict_types=1);

use App\Actions\Events\PrepareLiveShowAction;
use App\Actions\Events\TransitionLiveItemAction;
use App\Enums\Events\LiveItemOperation;
use App\Enums\Events\LiveShowStatus;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Events\Event;
use App\Models\Events\EventLiveRun;
use App\Models\Promotions\Promotion;
use App\ViewModels\Events\EventPackViewModel;
use App\ViewModels\Events\LiveShowViewModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

require_once __DIR__.'/../../../Fixtures/live-show.php';
require_once __DIR__.'/../../../Fixtures/member-actor.php';

test('the screen reports revised planned starts and actual delay without inventing missing times', function (): void {
    // Arrange
    travelTo(now()->startOfMinute());
    $fixture = liveShowFixture();
    resolve(TransitionLiveItemAction::class)->handle($fixture['event'], 1, $fixture['key'], LiveItemOperation::Start, signedInUser());
    $run = $fixture['run']->refresh();
    $item = $run->content['items'][0];
    $item['timezone'] = 'UTC';
    $planned = ['planned_at' => now()->subMinutes(20)->format('Y-m-d\TH:i'), 'revised_at' => now()->subMinutes(8)->format('Y-m-d\TH:i')];
    $comparison = function (array $timing, ?string $start) use ($run, $item, $fixture): string {
        $content = $run->content;
        $item['timing'] = $timing;
        $item['start'] = $start;
        $content['items'] = [$item];
        $run->content = $content;
        $run->save();

        return resolve(LiveShowViewModel::class)->data($fixture['event'])['runComparisons'][$fixture['key']];
    };
    $started = $item['start'];

    // Act / Assert
    expect($comparison($planned, $started))->toBe('Started 8 minutes late')
        ->and($comparison([...$planned, 'revised_at' => now()->addMinutes(5)->format('Y-m-d\TH:i')], $started))->toBe('Started 5 minutes early')
        ->and($comparison([...$planned, 'revised_at' => now()->format('Y-m-d\TH:i')], $started))->toBe('Started on time')
        ->and($comparison($planned, null))->toStartWith('Planned')
        ->and($comparison([], null))->toBe('No planned start');
});

test('the screen labels recorded times and shows missing ones as not recorded', function (): void {
    // Arrange
    travelTo(now()->startOfMinute());
    $fixture = liveShowFixture();
    resolve(TransitionLiveItemAction::class)->handle($fixture['event'], 1, $fixture['key'], LiveItemOperation::Start, signedInUser());

    // Act
    $screen = resolve(LiveShowViewModel::class)->data($fixture['event'], '2026-10-16T12:00:00+00:00');

    // Assert
    expect($screen['itemTimes'][$fixture['key']]['start'])->toBe(now()->utc()->format('M j H:i:s P'))
        ->and($screen['itemTimes'][$fixture['key']]['finish'])->toBe('Not recorded')
        ->and($screen['attendance'][$fixture['person']])->toBe(['arrived' => 'Not recorded', 'briefed' => 'Not recorded'])
        ->and($screen['refreshedLabel'])->toBe('Oct 16 12:00:00 +00:00')
        ->and($screen['savedLabel'])->toBe(now()->utc()->format('M j H:i:s P'));
});

test('a show that was never prepared has no times or comparisons to label', function (): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()->create(['date' => now()]);

    // Act
    $screen = resolve(LiveShowViewModel::class)->data($event);

    // Assert
    expect($screen['run'])->toBeNull()
        ->and($screen['refreshedLabel'])->toBeNull()
        ->and($screen['savedLabel'])->toBeNull()
        ->and($screen['runComparisons'])->toBeEmpty()
        ->and($screen['itemTimes'])->toBeEmpty();
});

test('the live screen data a member receives leaves out crew production content', function (): void {
    // Arrange
    require_once __DIR__.'/../../../Fixtures/production.php';
    $event = productionFixture()['event'];
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint'], administrator());

    $member = basicUser();
    Promotion::query()
        ->findOrFail($event->promotion_id)
        ->users()
        ->attach($member, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);
    actingAs($member);

    // Act
    $screen = resolve(LiveShowViewModel::class)->data($event);

    // Assert
    expect(json_encode(data_get($screen, 'run.content')))->not->toContain('PRIVATE-CREW-7Q', 'PRIVATE-CUE-7Q')
        ->and(data_get($screen, 'run.content'))->not->toHaveKey('production')
        ->and($screen['current'])->toBeNull()
        ->and($screen['next'])->not->toHaveKey('cues');
});

test('a member sees exactly the live show an administrator sees minus production, cues and crew rows', function (): void {
    // Arrange
    require_once __DIR__.'/../../../Fixtures/production.php';
    $fixture = productionFixture();
    $event = $fixture['event'];
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint'], administrator());
    $administratorScreen = resolve(LiveShowViewModel::class)->data($event);
    assert($administratorScreen['run'] instanceof EventLiveRun && $administratorScreen['source'] !== null);
    $administratorRun = $administratorScreen['run']->content;
    $administratorSource = $administratorScreen['source']['content'];
    actAsMemberOf($event);

    // Act
    $screen = resolve(LiveShowViewModel::class)->data($event);

    // Assert
    assert($screen['run'] instanceof EventLiveRun && $screen['source'] !== null);
    $run = $screen['run']->content;
    $source = $screen['source']['content'];
    $withoutCrew = fn (array $roster): array => array_values(array_filter($roster, fn (array $person): bool => ! str_starts_with($person['key'], 'crew:')));
    $withoutCues = fn (array $items): array => array_map(fn (array $item): array => array_diff_key($item, ['cues' => true]), $items);

    expect($administratorRun)->toHaveKey('production')
        ->and($administratorRun['items'][0])->toHaveKey('cues')
        ->and(array_column($administratorRun['roster'], 'key'))->toContain('crew:'.$fixture['contact']->id)
        ->and(array_keys($run))->toBe(array_values(array_diff(array_keys($administratorRun), ['production'])))
        ->and($run['items'])->toEqual($withoutCues($administratorRun['items']))
        ->and($run['roster'])->toBe($withoutCrew($administratorRun['roster']))
        ->and($source)->not->toHaveKey('production')
        ->and($source['items'])->toEqual($withoutCues($administratorSource['items']))
        ->and($source['roster'])->toBe($withoutCrew($administratorSource['roster']));
});

test('a member sees a completed live show without production content', function (): void {
    // Arrange
    require_once __DIR__.'/../../../Fixtures/production.php';
    $event = productionFixture()['event'];
    $data = resolve(EventPackViewModel::class)->data($event);
    $run = resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint'], administrator());
    $run->forceFill(['status' => LiveShowStatus::Completed])->save();
    actAsMemberOf($event);

    // Act
    $screen = resolve(LiveShowViewModel::class)->data($event);

    // Assert
    expect($screen['source'])->toBeNull()
        ->and($screen['complete'])->toBeTrue()
        ->and(data_get($screen, 'run.content'))->not->toHaveKey('production')
        ->and(json_encode(data_get($screen, 'run.content')))->not->toContain('PRIVATE-CREW-7Q', 'PRIVATE-CUE-7Q');
});
