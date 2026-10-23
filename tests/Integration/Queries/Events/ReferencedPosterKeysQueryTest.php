<?php

declare(strict_types=1);

use App\Models\Events\EventPublication;
use App\Models\Promotions\Promotion;
use App\Queries\Events\ReferencedPosterKeysQuery;

/** @return array{key: string, alt: string, caption: null} */
function referencedPoster(string $key): array
{
    return ['key' => $key, 'alt' => 'Poster', 'caption' => null];
}

describe('referenced poster keys query', function (): void {
    it('reads the poster key of every draft', function (): void {
        // Arrange
        $first = EventPublication::factory()->create();
        $first->forceFill(['draft' => [...$first->draft, 'poster' => referencedPoster('draft-one.png')]])->save();
        $second = EventPublication::factory()->create();
        $second->forceFill(['draft' => [...$second->draft, 'poster' => referencedPoster('draft-two.png')]])->save();

        // Act
        $keys = resolve(ReferencedPosterKeysQuery::class)->get();

        // Assert
        expect($keys)->toEqualCanonicalizing(['draft-one.png', 'draft-two.png']);
    });

    it('reads the poster key of a released card even when the draft has moved on', function (): void {
        // Arrange
        $publication = EventPublication::factory()->create();
        $publication->forceFill([
            'draft' => [...$publication->draft, 'poster' => referencedPoster('replacement.png')],
            'card' => ['poster' => referencedPoster('released.png')],
        ])->save();

        // Act
        $keys = resolve(ReferencedPosterKeysQuery::class)->get();

        // Assert
        expect($keys)->toEqualCanonicalizing(['replacement.png', 'released.png']);
    });

    it('skips drafts and cards that carry no poster', function (): void {
        // Arrange
        EventPublication::factory()->create(['card' => ['poster' => null]]);

        // Act
        $keys = resolve(ReferencedPosterKeysQuery::class)->get();

        // Assert
        expect($keys)->toBeEmpty();
    });

    it('reads publications of every promotion whatever promotion is enforced', function (): void {
        // Arrange
        $ownPromotion = Promotion::factory()->create();
        $other = EventPublication::factory()->create();
        $other->forceFill(['draft' => [...$other->draft, 'poster' => referencedPoster('elsewhere.png')]])->save();
        enforcePromotionContext($ownPromotion);

        // Act
        $keys = resolve(ReferencedPosterKeysQuery::class)->get();

        // Assert
        expect($keys)->toBe(['elsewhere.png']);
    });
});
