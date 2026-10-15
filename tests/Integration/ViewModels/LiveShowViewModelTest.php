<?php

declare(strict_types=1);

use App\Actions\Events\PrepareLiveShowAction;
use App\Actions\Events\TransitionLiveItemAction;
use App\Enums\Events\LiveItemOperation;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
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
    $item = $fixture['run']->refresh()->content['items'][0];
    $item['timing'] = ['planned_at' => now()->subMinutes(20)->format('Y-m-d\TH:i'), 'revised_at' => now()->subMinutes(8)->format('Y-m-d\TH:i')];
    $item['timezone'] = 'UTC';
    $screen = resolve(LiveShowViewModel::class);

    // Act / Assert
    expect($screen->comparison($item))->toBe('Started 8 minutes late');
    $item['timing']['revised_at'] = now()->addMinutes(5)->format('Y-m-d\TH:i');
    expect($screen->comparison($item))->toBe('Started 5 minutes early');
    $item['timing']['revised_at'] = now()->format('Y-m-d\TH:i');
    expect($screen->comparison($item))->toBe('Started on time');
    $item['start'] = null;
    expect($screen->comparison($item))->toStartWith('Planned');
    $item['timing'] = [];
    expect($screen->comparison($item))->toBe('No planned start')
        ->and($screen->actual(null, 'UTC'))->toBe('Not recorded');
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
