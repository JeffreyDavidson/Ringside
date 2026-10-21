<?php

declare(strict_types=1);

namespace App\Data\Events;

use App\Enums\Events\SponsorInstructionMode;
use App\ValueObjects\PackKey;

readonly class SponsorInstructionData
{
    public function __construct(
        public SponsorInstructionMode $mode,
        public ?PackKey $targetKey,
        public ?int $afterMatchId,
        public string $previewHash,
        public ?string $reason = null,
    ) {}
}
