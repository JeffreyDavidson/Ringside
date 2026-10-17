<?php

declare(strict_types=1);

namespace App\Data\Events;

use App\Models\Events\EventPublication;

/**
 * The editable public page draft of one event.
 *
 * @phpstan-import-type Poster from EventPublication
 * @phpstan-import-type Selection from EventPublication
 */
readonly class EventPublicationDraftData
{
    /**
     * @param  ?Poster  $poster
     * @param  list<Selection>  $matches
     * @param  list<int>  $results
     */
    public function __construct(
        public string $announcement,
        public ?string $summary,
        public ?string $notice,
        public bool $address,
        public ?string $doorsAt,
        public ?string $doorsOffset,
        public ?string $tickets,
        public ?array $poster,
        public array $matches,
        public array $results,
    ) {}

    /** @param array{announcement: string, summary: ?string, notice: ?string, address: bool, doors_at: ?string, doors_offset: ?string, tickets: ?string, poster: ?Poster, matches: list<Selection>, results: list<int>} $draft */
    public static function fromValidated(array $draft): self
    {
        return new self(
            $draft['announcement'],
            $draft['summary'],
            $draft['notice'],
            $draft['address'],
            $draft['doors_at'],
            $draft['doors_offset'],
            $draft['tickets'],
            $draft['poster'],
            $draft['matches'],
            $draft['results'],
        );
    }
}
