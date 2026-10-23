<?php

declare(strict_types=1);

use App\Exceptions\Events\EventPackConflictException;
use App\Exceptions\RecordChangedException;
use App\Lifecycle\Events\EventPackVersionRequirements;
use App\Models\Events\EventPack;

dataset('stale pack saves', [
    'a first save over an existing pack' => [fn (): ?EventPack => EventPack::factory()->make(['version' => 1, 'card_fingerprint' => 'card-a']), 0, 'card-a', 'card-a', false, RecordChangedException::class],
    'a repeated first save' => [fn (): ?EventPack => null, 1, 'card-a', 'card-a', false, RecordChangedException::class],
    'an older pack version' => [fn (): ?EventPack => EventPack::factory()->make(['version' => 3, 'card_fingerprint' => 'card-a']), 2, 'card-a', 'card-a', false, RecordChangedException::class],
    'a card that has moved on' => [fn (): ?EventPack => EventPack::factory()->make(['version' => 1, 'card_fingerprint' => 'card-a']), 1, 'card-a', 'card-b', true, RecordChangedException::class],
    'a pack saved for another card that was not reviewed' => [fn (): ?EventPack => EventPack::factory()->make(['version' => 1, 'card_fingerprint' => 'card-a']), 1, 'card-b', 'card-b', false, EventPackConflictException::class],
]);

dataset('allowed pack saves', [
    'a first save' => [fn (): ?EventPack => null, 0, 'card-a', 'card-a', false],
    'a save over the version and card the author saw' => [fn (): ?EventPack => EventPack::factory()->make(['version' => 2, 'card_fingerprint' => 'card-a']), 2, 'card-a', 'card-a', false],
    'a reviewed save over a changed card' => [fn (): ?EventPack => EventPack::factory()->make(['version' => 2, 'card_fingerprint' => 'card-a']), 2, 'card-b', 'card-b', true],
]);

describe('event pack version requirements', function (): void {
    it('refuses a save made against a stale pack, card or review', function (Closure $pack, int $version, string $fingerprint, string $currentFingerprint, bool $reviewed, string $exception): void {
        // Arrange
        $stored = $pack();

        // Act
        $attempt = fn () => resolve(EventPackVersionRequirements::class)->ensureCanSave($stored, $version, $fingerprint, $currentFingerprint, $reviewed);

        // Assert
        expect($attempt)->toThrow($exception);
    })->with('stale pack saves');

    it('lets a save through that matches the stored pack and the current card', function (Closure $pack, int $version, string $fingerprint, string $currentFingerprint, bool $reviewed): void {
        // Arrange
        $stored = $pack();

        // Act
        $attempt = fn () => resolve(EventPackVersionRequirements::class)->ensureCanSave($stored, $version, $fingerprint, $currentFingerprint, $reviewed);

        // Assert
        expect($attempt)->not->toThrow(Throwable::class);
    })->with('allowed pack saves');
});
