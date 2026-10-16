<?php

declare(strict_types=1);

namespace App\Data\Events;

use App\Models\Events\EventPack;

/**
 * The shared part of an event pack that one pack save writes. The production plan is saved by its own action and is
 * never part of this data.
 *
 * @phpstan-import-type MatchEntry from EventPack
 * @phpstan-import-type Segment from EventPack
 * @phpstan-import-type SponsorTask from EventPack
 */
readonly class EventPackData
{
    /**
     * @param  list<MatchEntry>  $matches
     * @param  list<Segment>  $segments
     * @param  ?list<SponsorTask>  $sponsorTasks  Null keeps the sponsor tasks already saved on the pack
     */
    public function __construct(
        public string $timezone,
        public ?string $doorsAt,
        public ?string $revision,
        public ?string $master,
        public ?string $notes,
        public ?string $followUp,
        public array $matches,
        public array $segments,
        public ?array $sponsorTasks,
    ) {}

    /** @param array{timezone: string, doors_at?: ?string, revision?: ?string, master?: ?string, notes?: ?string, follow_up?: ?string, matches: list<MatchEntry>, segments: list<Segment>, sponsor_tasks?: list<SponsorTask>} $content */
    public static function fromValidated(array $content): self
    {
        return new self(
            $content['timezone'],
            $content['doors_at'] ?? null,
            $content['revision'] ?? null,
            $content['master'] ?? null,
            $content['notes'] ?? null,
            $content['follow_up'] ?? null,
            $content['matches'],
            $content['segments'],
            $content['sponsor_tasks'] ?? null,
        );
    }
}
