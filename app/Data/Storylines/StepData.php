<?php

declare(strict_types=1);

namespace App\Data\Storylines;

readonly class StepData
{
    public function __construct(public string $heading, public string $description, public int $sequence, public SourceData $source, public string $submissionId, public ?string $reason = null) {}
}
