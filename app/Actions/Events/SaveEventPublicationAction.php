<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\EventPublicationDraftData;
use App\Lifecycle\Events\EventPublicationLock;
use App\Models\Events\Event;
use App\Models\Events\EventPublication;
use Illuminate\Support\Facades\DB;

class SaveEventPublicationAction
{
    public function __construct(private readonly EventPublicationLock $lock) {}

    public function handle(Event $event, int $version, string $fingerprint, EventPublicationDraftData $draft): EventPublication
    {
        return DB::transaction(function () use ($event, $version, $fingerprint, $draft): EventPublication {
            $locked = $this->lock->lock($event, $version, $fingerprint);
            $publication = $locked['publication'];
            $publication->fill(['draft' => [
                'announcement' => $draft->announcement,
                'summary' => $draft->summary,
                'notice' => $draft->notice,
                'address' => $draft->address,
                'doors_at' => $draft->doorsAt,
                'doors_offset' => $draft->doorsOffset,
                'tickets' => $draft->tickets,
                'poster' => $draft->poster,
                'matches' => $draft->matches,
                'results' => $draft->results,
            ]]);
            $publication->version++;
            $publication->save();

            return $publication;
        }, attempts: 3);
    }
}
