<?php

declare(strict_types=1);

use App\Actions\Events\PrepareLiveShowAction;
use App\Actions\Events\TransitionLiveItemAction;
use App\Enums\Events\LiveItemOperation;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\ViewModels\EventPackViewModel;
use App\ViewModels\LiveShowViewModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

require_once __DIR__.'/../../Fixtures/live-show.php';

test('the screen reports revised planned starts and actual delay without inventing missing times', function (): void {
    // Arrange
    travelTo(now()->startOfMinute());
    $fixture = liveShowFixture();
    resolve(TransitionLiveItemAction::class)->handle($fixture['event'], 1, $fixture['key'], LiveItemOperation::Start);
    $run = $fixture['run']->refresh();
    $item = $run->content['items'][0];
    $item['timing'] = ['planned_at' => now()->subMinutes(20)->format('Y-m-d\TH:i'), 'revised_at' => now()->subMinutes(8)->format('Y-m-d\TH:i')];
    $item['timezone'] = 'UTC';
    $comparison = function (array $changes) use ($run, $item, $fixture): string {
        $run->content = [...$run->content, 'items' => [[...$item, ...$changes]]];
        $run->save();

        return resolve(LiveShowViewModel::class)->data($fixture['event'])['runComparisons'][$fixture['key']];
    };

    // Act / Assert
    expect($comparison([]))->toBe('Started 8 minutes late')
        ->and($comparison(['timing' => [...$item['timing'], 'revised_at' => now()->addMinutes(5)->format('Y-m-d\TH:i')]]))->toBe('Started 5 minutes early')
        ->and($comparison(['timing' => [...$item['timing'], 'revised_at' => now()->format('Y-m-d\TH:i')]]))->toBe('Started on time')
        ->and($comparison(['start' => null]))->toStartWith('Planned')
        ->and($comparison(['start' => null, 'timing' => []]))->toBe('No planned start');
});

test('the screen labels recorded times and shows missing ones as not recorded', function (): void {
    // Arrange
    travelTo(now()->startOfMinute());
    $fixture = liveShowFixture();
    resolve(TransitionLiveItemAction::class)->handle($fixture['event'], 1, $fixture['key'], LiveItemOperation::Start);

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
    require_once __DIR__.'/../../Fixtures/production.php';
    $event = productionFixture()['event'];
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(PrepareLiveShowAction::class)->handle($event, 0, 1, $data['card']['fingerprint']);

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
