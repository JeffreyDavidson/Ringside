<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Enums\Events\PublicSection;
use App\Exceptions\Events\EventPublicationException;
use App\Models\Events\Event;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

class UnpublishEventPublicationAction
{
    public function __construct(private readonly LockEventPublicationAction $lock) {}

    public function handle(Event $event, User $actor, int $version, string $fingerprint, PublicSection $section): void
    {
        DB::transaction(function () use ($event, $actor, $version, $fingerprint, $section): void {
            $locked = $this->lock->handle($event, $version, $fingerprint);
            $publication = $locked['publication'];

            if (! $publication->exists) {
                throw EventPublicationException::invalid();
            }

            $publication->results_published_at = null;

            if ($section === PublicSection::Card) {
                $publication->published_at = null;
                $publication->unpublished_at = now();
            }

            $publication->version++;
            $publication->save();

            activity('publication')
                ->performedOn($locked['event'])
                ->causedBy($actor)
                ->withProperties([
                    'publication_id' => $publication->id,
                    'section' => $section->value,
                    'version' => $publication->version,
                ])
                ->log('unpublished');
        }, attempts: 3);
    }
}
