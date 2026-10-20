<?php

declare(strict_types=1);

namespace App\Lifecycle\Events\PublicationReleases;

use App\Exceptions\Events\EventPublicationException;
use App\Models\Events\Event;
use App\Models\Events\EventPublication;
use App\Models\Users\User;
use App\Services\Events\PublicEventSourceService;

/**
 * One public section of an event's publication: it checks that the saved draft and the event's current source allow
 * releasing that section and fills the publication's fields for it. It never writes; the calling Action locks the
 * publication, bumps its version, saves it and logs the release.
 *
 * @phpstan-import-type Source from PublicEventSourceService
 */
interface PublicationRelease
{
    /**
     * @param  Source  $source
     *
     * @throws EventPublicationException when the draft or the event's current state does not allow the release
     */
    public function apply(EventPublication $publication, Event $event, User $actor, array $source): void;
}
