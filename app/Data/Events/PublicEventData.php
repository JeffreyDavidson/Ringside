<?php

declare(strict_types=1);

namespace App\Data\Events;

use App\Models\Events\EventPublication;

/** @phpstan-import-type Row from EventPublication */
readonly class PublicEventData
{
    /** @param list<Row> $rows */
    public function __construct(
        public string $title, public string $promotion, public string $announcement,
        public ?string $summary, public ?string $notice, public ?string $date, public string $timezone,
        public ?string $venue, public ?string $address, public ?string $doors, public ?string $tickets,
        public ?string $posterUrl, public ?string $posterAlt, public ?string $posterCaption,
        public array $rows, public string $cardUrl, public ?string $resultsUrl,
        public bool $isResults = false, public bool $isPreview = false,
        public bool $scheduleChanged = false, public bool $resultsChanged = false,
        public ?string $correctedAt = null,
    ) {}
}
