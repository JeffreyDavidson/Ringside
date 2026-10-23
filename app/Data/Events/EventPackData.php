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
 * @phpstan-import-type Production from EventPack
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

    /**
     * The content to store when this data is saved over the given pack. Matches already on the pack and omitted from this
     * data are kept (a removed match keeps its notes; identity is never inferred from card position), and so are the
     * sponsor tasks when this data carries none and the production plan the pack holds.
     *
     * @return array{timezone: string, doors_at: ?string, revision: ?string, master: ?string, notes: ?string, follow_up: ?string, matches: list<MatchEntry>, segments: list<Segment>, sponsor_tasks?: list<SponsorTask>, production?: Production}
     */
    public function contentOver(?EventPack $pack): array
    {
        $content = [
            'timezone' => $this->timezone,
            'doors_at' => $this->doorsAt,
            'revision' => $this->revision,
            'master' => $this->master,
            'notes' => $this->notes,
            'follow_up' => $this->followUp,
            'matches' => array_values(collect($pack?->content['matches'] ?? [])->keyBy('match_id')
                ->replace(collect($this->matches)->keyBy('match_id'))->all()),
            'segments' => $this->segments,
        ];
        $sponsorTasks = $this->sponsorTasks ?? $pack->content['sponsor_tasks'] ?? null;

        if ($sponsorTasks !== null) {
            $content['sponsor_tasks'] = $sponsorTasks;
        }

        if (isset($pack?->content['production'])) {
            $content['production'] = $pack->content['production'];
        }

        return $content;
    }

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
