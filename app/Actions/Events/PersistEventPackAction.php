<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\EventPackData;
use App\Models\Events\Event;
use App\Models\Events\EventPack;

/**
 * Write the next version of an event's pack. Runs inside the caller's transaction, after the card lock and the version
 * and fingerprint checks.
 *
 * @phpstan-import-type Content from EventPack
 */
class PersistEventPackAction
{
    /**
     * @param  EventPackData|Content  $content  Pack data is merged over the pack first; a full content is written as given
     */
    public function handle(Event $event, ?EventPack $pack, int $version, string $fingerprint, EventPackData|array $content): EventPack
    {
        $pack ??= new EventPack;
        $pack->event_id = $event->id;
        $pack->version = $version + 1;
        $pack->card_fingerprint = $fingerprint;
        $pack->fill(['content' => $content instanceof EventPackData ? $this->merged($content, $pack) : $content]);
        $pack->save();

        return $pack;
    }

    /**
     * Retain removed-match notes and omitted entries; identity is never inferred from card position. Sponsor tasks the
     * data omits and the production plan stay as saved.
     *
     * @return Content
     */
    private function merged(EventPackData $data, EventPack $pack): array
    {
        $content = [
            'timezone' => $data->timezone,
            'doors_at' => $data->doorsAt,
            'revision' => $data->revision,
            'master' => $data->master,
            'notes' => $data->notes,
            'follow_up' => $data->followUp,
            'matches' => array_values(collect($pack->content['matches'] ?? [])->keyBy('match_id')
                ->replace(collect($data->matches)->keyBy('match_id'))->all()),
            'segments' => $data->segments,
        ];
        $sponsorTasks = $data->sponsorTasks ?? $pack->content['sponsor_tasks'] ?? null;

        if ($sponsorTasks !== null) {
            $content['sponsor_tasks'] = $sponsorTasks;
        }

        if (isset($pack->content['production'])) {
            $content['production'] = $pack->content['production'];
        }

        return $content;
    }
}
