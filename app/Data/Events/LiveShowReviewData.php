<?php

declare(strict_types=1);

namespace App\Data\Events;

/** The operator's reasons for skipping the pending items that the card no longer lists, keyed by item key. */
readonly class LiveShowReviewData
{
    /** @param array<string, string> $removedReasons */
    public function __construct(public array $removedReasons) {}

    /** @param array<string, string> $removedReasons */
    public static function fromValidated(array $removedReasons): self
    {
        return new self($removedReasons);
    }
}
