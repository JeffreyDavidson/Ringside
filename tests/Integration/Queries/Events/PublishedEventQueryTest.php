<?php

declare(strict_types=1);

use App\Models\Events\EventPublication;
use App\Queries\Events\PublishedEventQuery;

describe('published event query', function (): void {
    it('reads a published publication with its event for a public link', function (): void {
        // Arrange
        $fixture = publicEventFixture();
        $publication = EventPublication::factory()->for($fixture['event'])->create(['published_at' => now()]);

        // Act
        $found = resolve(PublishedEventQuery::class)->get($publication->public_id, $publication->slug);

        // Assert
        expect($found?->is($publication))->toBeTrue()
            ->and($found?->event->is($fixture['event']))->toBeTrue();
    });

    it('reads nothing for a link that names no publication', function (string $link): void {
        // Arrange
        $fixture = publicEventFixture();
        $publication = EventPublication::factory()->for($fixture['event'])->create(['published_at' => now()]);
        $publicId = $link === 'public id' ? 'unknown-public-id' : $publication->public_id;
        $slug = $link === 'slug' ? 'unknown-slug' : $publication->slug;

        // Act
        $found = resolve(PublishedEventQuery::class)->get($publicId, $slug);

        // Assert
        expect($found)->toBeNull();
    })->with(['public id', 'slug']);

    it('reads nothing for a publication that was never released', function (): void {
        // Arrange
        $fixture = publicEventFixture();
        $publication = EventPublication::factory()->for($fixture['event'])->create(['published_at' => null]);

        // Act
        $found = resolve(PublishedEventQuery::class)->get($publication->public_id, $publication->slug);

        // Assert
        expect($found)->toBeNull();
    });

    it('reads nothing when the event was deleted without unpublishing', function (): void {
        // Arrange
        $fixture = publicEventFixture();
        $publication = EventPublication::factory()->for($fixture['event'])->create(['published_at' => now()]);
        $fixture['event']->delete();

        // Act
        $found = resolve(PublishedEventQuery::class)->get($publication->public_id, $publication->slug);

        // Assert
        expect($found)->toBeNull();
    });
});
