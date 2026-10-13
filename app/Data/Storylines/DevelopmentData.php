<?php

declare(strict_types=1);

namespace App\Data\Storylines;

readonly class DevelopmentData
{
    /** @param list<int> $participantIds */
    public function __construct(public string $heading, public string $description, public string $occurredOn, public int $sequence, public array $participantIds, public SourceData $source, public string $submissionId, public ?int $stepId = null, public ?string $reason = null) {}
}
