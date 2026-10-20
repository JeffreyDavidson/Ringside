<?php

declare(strict_types=1);

namespace App\Lifecycle\Events\PublicationReleases;

use App\Exceptions\Events\EventPublicationException;
use App\Models\Events\Event;
use App\Models\Events\EventPublication;
use App\Models\Users\User;
use App\Services\Events\PublicEventCardService;
use Illuminate\Support\Str;

/** Releases the card: the title, schedule and the announced match rows. Every match on it must have a label. */
final readonly class ReleaseCard implements PublicationRelease
{
    public function __construct(private PublicationDraftRequirements $draft, private PublicEventCardService $cards) {}

    public function apply(EventPublication $publication, Event $event, User $actor, array $source): void
    {
        $announcement = $this->draft->ensureDraftReleasable($publication, $event, $source);
        $draft = $publication->draft;
        $rows = $this->cards->rows($source, $draft['matches']);
        if (collect($rows)->contains(fn (array $row): bool => $row['label'] === '')) {
            throw EventPublicationException::invalid();
        }
        if ($publication->card === null) {
            $publication->slug = Str::limit(Str::slug($source['title']), 120, '') ?: 'event';
        }
        $publication->card = $this->cards->card($source, $draft);
        $publication->card_source = $draft['matches'];
        $publication->schedule_fingerprint = $source['schedule'];
        $publication->card_fingerprint = $this->cards->cardFingerprint($source, $draft['matches']);
        $publication->published_at = now();
        $publication->published_by = $actor->id;
        $publication->unpublished_at = null;
        if ($announcement->isNotice()) {
            $publication->results_published_at = null;
        }
    }
}
