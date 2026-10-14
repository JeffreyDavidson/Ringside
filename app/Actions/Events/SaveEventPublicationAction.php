<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Models\Events\Event;
use App\Models\Events\EventPublication;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

/** @phpstan-import-type Draft from EventPublication */
class SaveEventPublicationAction
{
    public function __construct(private readonly LockEventPublicationAction $lock) {}

    /** @param Draft $draft */
    public function handle(Event $event, User $actor, int $version, string $fingerprint, array $draft): EventPublication
    {
        return DB::transaction(function () use ($event, $actor, $version, $fingerprint, $draft): EventPublication {
            $locked = $this->lock->handle($event, $actor, $version, $fingerprint);
            $publication = $locked['publication'];
            $publication->draft = $draft;
            $publication->version++;
            $publication->save();

            return $publication;
        }, attempts: 3);
    }
}
