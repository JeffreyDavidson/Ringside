<?php

declare(strict_types=1);

use App\Models\Events\EventPublication;
use App\Models\Promotions\Promotion;
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

    it('reads the publication and its event in one query plus the eager load of that result', function (): void {
        // Arrange
        $fixture = publicEventFixture();
        $publication = EventPublication::factory()->for($fixture['event'])->create(['published_at' => now()]);
        $query = resolve(PublishedEventQuery::class);

        // Act
        $statements = recordStatements(fn () => $query->get($publication->public_id, $publication->slug)?->event);

        // Assert
        expect($statements)->toHaveCount(2)
            ->and($statements[0]['sql'])->toContain('from "event_publications"');
    });

    it('reads nothing when the publication names an event of another promotion', function (): void {
        // Arrange
        $fixture = publicEventFixture();
        $publication = EventPublication::factory()->for($fixture['event'])->create(['published_at' => now()]);
        $publication->forceFill(['promotion_id' => Promotion::factory()->create()->id])->save();

        // Act
        $found = resolve(PublishedEventQuery::class)->get($publication->public_id, $publication->slug);

        // Assert
        expect($found)->toBeNull();
    });

    it('reads nothing for a link that names no publication', function (string $link): void {
        // Arrange
        $fixture = publicEventFixture();
        $publication = EventPublication::factory()->for($fixture['event'])->create(['published_at' => now()]);
        $publicId = $link === 'public id' ? '00000000-0000-4000-8000-000000000000' : $publication->public_id;
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
