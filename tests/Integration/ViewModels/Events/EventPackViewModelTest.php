<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\ViewModels\Events\EventPackViewModel;

require_once __DIR__.'/../../../Fixtures/member-actor.php';
require_once __DIR__.'/../../../Fixtures/production.php';

test('a member sees the pack without its production block while the saved pack keeps it', function (): void {
    // Arrange
    $event = productionFixture()['event'];
    $administratorData = resolve(EventPackViewModel::class)->data($event);
    actAsMemberOf($event);

    // Act
    $data = resolve(EventPackViewModel::class)->data($event);

    // Assert
    expect($data['content'])->not->toHaveKey('production')
        ->and($data['content'])->toEqual(array_diff_key($administratorData['content'], ['production' => true]))
        ->and($data['pack']?->content)->toEqual($data['content'])
        ->and($administratorData['content'])->toHaveKey('production')
        ->and($event->pack()->firstOrFail()->content)->toHaveKey('production');
});

test('an administrator sees the pack with its production block', function (): void {
    // Arrange
    $fixture = productionFixture();

    // Act
    $data = resolve(EventPackViewModel::class)->data($fixture['event']);

    // Assert
    expect($data['content']['production'] ?? null)->toEqual($fixture['production'])
        ->and($data['pack']?->content['production'] ?? null)->toEqual($fixture['production']);
});

test('a member of an event with no saved pack sees the default pack content', function (): void {
    // Arrange
    $event = Event::factory()->for(Promotion::factory())->create(['date' => now()]);
    actAsMemberOf($event);

    // Act
    $data = resolve(EventPackViewModel::class)->data($event);

    // Assert
    expect($data['pack'])->toBeNull()
        ->and($data['content'])->not->toHaveKey('production')
        ->and(array_keys($data['content']))->toBe(['timezone', 'doors_at', 'revision', 'master', 'notes', 'follow_up', 'matches', 'segments']);
});
