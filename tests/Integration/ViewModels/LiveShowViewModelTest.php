<?php

declare(strict_types=1);

use App\Actions\Events\TransitionLiveItemAction;
use App\Enums\Events\LiveItemOperation;
use App\ViewModels\LiveShowViewModel;

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
