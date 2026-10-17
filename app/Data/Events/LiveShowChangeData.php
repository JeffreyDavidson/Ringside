<?php

declare(strict_types=1);

namespace App\Data\Events;

/** One validated live operation to persist and audit, with what the target held before and after. */
readonly class LiveShowChangeData
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function __construct(
        public string $operation,
        public string $target,
        public array $before,
        public array $after,
        public ?string $reason = null,
    ) {}
}
