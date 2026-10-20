<?php

declare(strict_types=1);

namespace App\Lifecycle\Events\PublicationReleases;

use App\Enums\Events\PublicSection;

/** Picks the release that publishes a public section of an event. */
final readonly class PublicationReleaseResolver
{
    public function __construct(private ReleaseCard $card, private ReleaseResults $results) {}

    public function resolve(PublicSection $section): PublicationRelease
    {
        return match ($section) {
            PublicSection::Card => $this->card,
            PublicSection::Results => $this->results,
        };
    }
}
