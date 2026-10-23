<?php

declare(strict_types=1);

use App\Actions\Events\PruneUnusedPostersAction;
use App\Models\Events\EventPublication;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('local'));

function storePoster(string $key, int $hoursOld): void
{
    Storage::disk('local')->put('event-posters/'.$key, 'png');
    touch(Storage::disk('local')->path('event-posters/'.$key), now()->subHours($hoursOld)->getTimestamp());
}

function publicationWithPosters(?string $draftKey, ?string $cardKey): void
{
    $publication = EventPublication::factory()->create();
    $publication->forceFill([
        'draft' => [...$publication->draft, 'poster' => $draftKey === null ? null : ['key' => $draftKey, 'alt' => 'Poster', 'caption' => null]],
        'card' => $cardKey === null ? null : ['poster' => ['key' => $cardKey, 'alt' => 'Poster', 'caption' => null]],
    ])->save();
}

describe('prune unused posters action', function (): void {
    it('keeps an old poster the draft still uses', function (): void {
        // Arrange
        storePoster('draft.png', 72);
        publicationWithPosters('draft.png', null);

        // Act
        $removed = resolve(PruneUnusedPostersAction::class)->handle(now()->subDay(), false);

        // Assert
        expect($removed)->toBeEmpty()
            ->and(Storage::disk('local')->exists('event-posters/draft.png'))->toBeTrue();
    });

    it('keeps an old poster only the released card still uses', function (): void {
        // Arrange
        storePoster('released.png', 72);
        publicationWithPosters(null, 'released.png');

        // Act
        $removed = resolve(PruneUnusedPostersAction::class)->handle(now()->subDay(), false);

        // Assert
        expect($removed)->toBeEmpty()
            ->and(Storage::disk('local')->exists('event-posters/released.png'))->toBeTrue();
    });

    it('deletes an old poster nothing uses', function (): void {
        // Arrange
        storePoster('orphan.png', 72);

        // Act
        $removed = resolve(PruneUnusedPostersAction::class)->handle(now()->subDay(), false);

        // Assert
        expect($removed)->toBe(['event-posters/orphan.png'])
            ->and(Storage::disk('local')->exists('event-posters/orphan.png'))->toBeFalse();
    });

    it('keeps a new poster nothing uses yet', function (): void {
        // Arrange
        storePoster('fresh.png', 1);

        // Act
        $removed = resolve(PruneUnusedPostersAction::class)->handle(now()->subDay(), false);

        // Assert
        expect($removed)->toBeEmpty()
            ->and(Storage::disk('local')->exists('event-posters/fresh.png'))->toBeTrue();
    });

    it('reports what it would delete in a dry run and deletes nothing', function (): void {
        // Arrange
        storePoster('orphan.png', 72);

        // Act
        $removed = resolve(PruneUnusedPostersAction::class)->handle(now()->subDay(), true);

        // Assert
        expect($removed)->toBe(['event-posters/orphan.png'])
            ->and(Storage::disk('local')->exists('event-posters/orphan.png'))->toBeTrue();
    });

    it('leaves files outside the poster folder alone', function (): void {
        // Arrange
        Storage::disk('local')->put('other/old.png', 'png');
        touch(Storage::disk('local')->path('other/old.png'), now()->subDays(3)->getTimestamp());

        // Act
        $removed = resolve(PruneUnusedPostersAction::class)->handle(now()->subDay(), false);

        // Assert
        expect($removed)->toBeEmpty()
            ->and(Storage::disk('local')->exists('other/old.png'))->toBeTrue();
    });
});
