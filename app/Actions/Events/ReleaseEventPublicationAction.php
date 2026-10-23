<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Enums\Events\PublicSection;
use App\Lifecycle\Events\PublicationReleases\PublicationReleaseResolver;
use App\Models\Events\Event;
use App\Models\Events\EventPublication;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

class ReleaseEventPublicationAction
{
    public function __construct(private readonly LockEventPublicationAction $lock, private readonly PublicationReleaseResolver $releases) {}

    public function handle(Event $event, User $actor, int $version, string $fingerprint, PublicSection $section): void
    {
        DB::transaction(function () use ($event, $actor, $version, $fingerprint, $section): void {
            $locked = $this->lock->handle($event, $version, $fingerprint);
            $publication = $locked['publication'];
            $event = $locked['event'];
            $this->releases->resolve($section)->apply($publication, $event, $actor, $locked['source']);
            $this->save($publication, $event, $actor, $section);
        }, attempts: 3);
    }

    private function save(EventPublication $publication, Event $event, User $actor, PublicSection $section): void
    {
        $publication->version++;
        $publication->save();

        activity('publication')
            ->performedOn($event)
            ->causedBy($actor)
            ->withProperties([
                'publication_id' => $publication->id,
                'section' => $section->value,
                'version' => $publication->version,
            ])
            ->log('published');
    }
}
