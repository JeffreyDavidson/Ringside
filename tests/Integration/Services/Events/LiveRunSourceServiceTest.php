<?php

declare(strict_types=1);

require_once __DIR__.'/../../../Fixtures/live-show.php';

use App\Services\Events\LiveRunSourceService;
use App\Services\Events\LiveShowSourceService;

test('a run still matches the pack and card it was prepared from', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $source = resolve(LiveShowSourceService::class)->get($fixture['event']);
    assert($source !== null);

    // Act
    $moved = resolve(LiveRunSourceService::class)->hasMovedOn($fixture['run'], $source['pack'], $source['fingerprint']);

    // Assert
    expect($moved)->toBeFalse();
});

test('a run has moved on when the pack is gone, its version changed or the card changed', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $run = $fixture['run'];
    $source = resolve(LiveShowSourceService::class)->get($fixture['event']);
    assert($source !== null);
    $newerPack = clone $source['pack'];
    $newerPack->version = $run->pack_version + 1;
    $comparison = resolve(LiveRunSourceService::class);

    // Act / Assert
    expect($comparison->hasMovedOn($run, null, $source['fingerprint']))->toBeTrue()
        ->and($comparison->hasMovedOn($run, $newerPack, $source['fingerprint']))->toBeTrue()
        ->and($comparison->hasMovedOn($run, $source['pack'], 'a-different-card'))->toBeTrue();
});
