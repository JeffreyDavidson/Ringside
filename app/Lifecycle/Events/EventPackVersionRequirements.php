<?php

declare(strict_types=1);

namespace App\Lifecycle\Events;

use App\Exceptions\Events\EventPackConflictException;
use App\Exceptions\RecordChangedException;
use App\Models\Events\EventPack;

/**
 * A pack or production save only lands on the pack version and card the author reviewed: the stored version must be the
 * one submitted, the submitted fingerprint must be the card's current one, and a pack saved for a different card needs
 * the author to confirm they reviewed the changes.
 */
final class EventPackVersionRequirements
{
    /**
     * Refuse a save made against a stale pack version or card, or one over a changed card that was not reviewed. Call it
     * inside the card lock, with the pack the lock returned and the fingerprint just computed from the locked card.
     *
     * @throws RecordChangedException
     * @throws EventPackConflictException
     */
    public function ensureCanSave(?EventPack $pack, int $version, string $fingerprint, string $currentFingerprint, bool $reviewed): void
    {
        if (($pack->version ?? 0) !== $version || ! hash_equals($currentFingerprint, $fingerprint)) {
            throw RecordChangedException::changed();
        }

        if ($pack instanceof EventPack && $pack->card_fingerprint !== $fingerprint && ! $reviewed) {
            throw EventPackConflictException::reviewRequired();
        }
    }
}
