<?php

declare(strict_types=1);

namespace App\Data\Storylines;

readonly class SourceData
{
    public function __construct(public ?int $eventId = null, public ?int $matchId = null, public ?string $segmentId = null, public ?string $targetOn = null, public ?string $fingerprint = null) {}
}
