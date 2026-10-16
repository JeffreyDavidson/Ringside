<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Enums\Events\PublicAnnouncement;
use App\Enums\Events\PublicSection;
use App\Exceptions\Events\EventPublicationException;
use App\Models\Events\Event;
use App\Models\Events\EventPublication;
use App\Models\Users\User;
use App\Queries\Events\PublicEventSourceQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReleaseEventPublicationAction
{
    public function __construct(private readonly LockEventPublicationAction $lock, private readonly PublicEventSourceQuery $source) {}

    public function handle(Event $event, User $actor, int $version, string $fingerprint, PublicSection $section): void
    {
        DB::transaction(function () use ($event, $actor, $version, $fingerprint, $section): void {
            $locked = $this->lock->handle($event, $version, $fingerprint);
            $publication = $locked['publication'];
            $source = $locked['source'];
            $event = $locked['event'];
            if (! $publication->exists) {
                throw EventPublicationException::invalid();
            }
            $draft = $publication->draft;
            $announcement = PublicAnnouncement::from($draft['announcement']);
            $selected = array_column($draft['matches'], 'id');
            $available = array_column($source['matches'], 'id');
            if (array_diff($selected, $available) !== [] || ($event->date === null && ! $announcement->isNotice())
                || ($announcement->isNotice() && ($event->date?->isPast() || ! $draft['notice']))) {
                throw EventPublicationException::invalid();
            }
            if ($draft['poster'] !== null && ! Storage::disk('local')->exists('event-posters/'.$draft['poster']['key'])) {
                throw EventPublicationException::invalid();
            }
            if ($section === PublicSection::Results) {
                if ($announcement->isNotice() || $publication->published_at === null || $publication->card === null || $event->date === null || $event->date->isFuture()
                    || $draft['results'] === [] || array_diff($draft['results'], array_column($publication->card_source ?? [], 'id')) !== []
                    || $publication->schedule_fingerprint !== $source['schedule']
                    || $publication->card_fingerprint !== $this->source->cardFingerprint($source, $publication->card_source ?? [])) {
                    throw EventPublicationException::invalid();
                }
                $results = $this->source->resultSource($source, $draft['results']);
                if (count($results) !== count($draft['results'])) {
                    throw EventPublicationException::invalid();
                }
                $rows = [];
                foreach ($results as $match) {
                    if ($match['finish'] === null || $match['label'] === '' || ($match['winner'] === '')) {
                        throw EventPublicationException::invalid();
                    }
                    $rows[] = ['label' => $match['label'], 'details' => '', 'outcome' => $match['winner'] === null ? $match['finish'] : $match['winner'].' — '.$match['finish']];
                }
                $publication->results = ['rows' => $rows, 'corrected_at' => $publication->results === null ? null : now()->toIso8601String()];
                $publication->results_source = $draft['results'];
                $publication->results_fingerprint = $this->source->fingerprint($results);
                $publication->results_published_at = now();
                $publication->results_published_by = $actor->id;
                $this->save($publication, $event, $actor, 'results');

                return;
            }
            $rows = $this->source->rows($source, $draft['matches']);
            if (collect($rows)->contains(fn (array $row): bool => $row['label'] === '')) {
                throw EventPublicationException::invalid();
            }
            if ($publication->card === null) {
                $publication->slug = Str::limit(Str::slug($source['title']), 120, '') ?: 'event';
            }
            $publication->card = $this->source->card($source, $draft);
            $publication->card_source = $draft['matches'];
            $publication->schedule_fingerprint = $source['schedule'];
            $publication->card_fingerprint = $this->source->cardFingerprint($source, $draft['matches']);
            $publication->published_at = now();
            $publication->published_by = $actor->id;
            $publication->unpublished_at = null;
            if ($announcement->isNotice()) {
                $publication->results_published_at = null;
            }
            $this->save($publication, $event, $actor, 'card');
        }, attempts: 3);
    }

    private function save(EventPublication $publication, Event $event, User $actor, string $section): void
    {
        $publication->version++;
        $publication->save();
        activity('publication')->performedOn($event)->causedBy($actor)->withProperties(['publication_id' => $publication->id, 'section' => $section, 'version' => $publication->version])->log('published');
    }
}
