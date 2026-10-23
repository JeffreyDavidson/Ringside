<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\EventPackData;
use App\Lifecycle\Events\EventCardLock;
use App\Lifecycle\Events\EventPackVersionRequirements;
use App\Models\Events\Event;
use App\Services\Events\EventPackCardService;
use Illuminate\Support\Facades\DB;

class SaveEventPackAction
{
    public function __construct(
        private readonly EventPackCardService $card,
        private readonly EventCardLock $lock,
        private readonly EventPackVersionRequirements $requirements,
        private readonly PersistEventPackAction $persist,
    ) {}

    public function handle(Event $event, int $version, string $fingerprint, EventPackData $data, bool $reviewed): void
    {
        DB::transaction(function () use ($event, $version, $fingerprint, $data, $reviewed): void {
            // Card assignment actions also lock the owning event before changing its matches.
            ['event' => $event, 'pack' => $pack] = $this->lock->lock($event->id);
            $card = $this->card->get($event);

            $this->requirements->ensureCanSave($pack, $version, $fingerprint, $card['fingerprint'], $reviewed);

            $this->persist->handle($event, $pack, $version, $fingerprint, $data);
        }, attempts: 3);
    }
}
