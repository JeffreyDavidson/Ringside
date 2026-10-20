<?php

declare(strict_types=1);

use App\Enums\Events\PublicSection;
use App\Lifecycle\Events\PublicationReleases\PublicationReleaseResolver;
use App\Lifecycle\Events\PublicationReleases\ReleaseCard;
use App\Lifecycle\Events\PublicationReleases\ReleaseResults;

test('it picks the release for each public section', function (PublicSection $section, string $release): void {
    // Act
    $resolved = resolve(PublicationReleaseResolver::class)->resolve($section);

    // Assert
    expect($resolved::class)->toBe($release);
})->with([
    'card' => [PublicSection::Card, ReleaseCard::class],
    'results' => [PublicSection::Results, ReleaseResults::class],
]);
