<?php

declare(strict_types=1);

namespace App\Data\Events;

use App\Enums\Events\SponsorCommitmentStatus;
use App\Enums\Events\SponsorCommitmentType;
use Illuminate\Support\Carbon;

readonly class SponsorCommitmentData
{
    public function __construct(
        public int $sponsorId,
        public SponsorCommitmentType $type,
        public string $description,
        public ?string $owner = null,
        public ?Carbon $deadline = null,
        public string $timezone = 'UTC',
        public ?string $notes = null,
        public ?string $agreement = null,
        public SponsorCommitmentStatus $status = SponsorCommitmentStatus::Pending,
        public ?string $evidence = null,
        public ?string $evidenceUrl = null,
        public ?Carbon $completedAt = null,
        public ?string $instructionLabel = null,
        public ?string $instruction = null,
        public ?string $instructionOwner = null,
    ) {}
}
