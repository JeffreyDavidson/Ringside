<?php

declare(strict_types=1);

namespace App\Lifecycle\Events\PublicationReleases;

use App\Exceptions\Events\EventPublicationException;
use App\Models\Events\Event;
use App\Models\Events\EventPublication;
use App\Models\Users\User;
use App\Services\Events\PublicEventCardService;

/**
 * Releases the selected results of a show that has happened. The card must already be released and unchanged since,
 * and every selected match must be on that card and recorded with a finish and a label.
 */
final readonly class ReleaseResults implements PublicationRelease
{
    public function __construct(private PublicationDraftRequirements $draft, private PublicEventCardService $cards) {}

    public function apply(EventPublication $publication, Event $event, User $actor, array $source): void
    {
        $this->draft->ensureDraftReleasable($publication, $event, $source);
        $draft = $publication->draft;
        if ($publication->published_at === null || $publication->card === null || $event->date === null || $event->date->isFuture()
            || $draft['results'] === [] || array_diff($draft['results'], array_column($publication->card_source ?? [], 'id')) !== []
            || $publication->schedule_fingerprint !== $source['schedule']
            || $publication->card_fingerprint !== $this->cards->cardFingerprint($source, $publication->card_source ?? [])) {
            throw EventPublicationException::invalid();
        }
        $results = $this->cards->resultSource($source, $draft['results']);
        if (count($results) !== count($draft['results'])) {
            throw EventPublicationException::invalid();
        }
        $rows = [];
        foreach ($results as $match) {
            if ($match['finish'] === null || $match['winner'] === '') {
                throw EventPublicationException::invalid();
            }
            $rows[] = ['label' => $match['label'], 'details' => '', 'outcome' => $match['winner'] === null ? $match['finish'] : $match['winner'].' — '.$match['finish']];
        }
        $publication->results = ['rows' => $rows, 'corrected_at' => $publication->results === null ? null : now()->toIso8601String()];
        $publication->results_source = $draft['results'];
        $publication->results_fingerprint = $this->cards->fingerprint($results);
        $publication->results_published_at = now();
        $publication->results_published_by = $actor->id;
    }
}
