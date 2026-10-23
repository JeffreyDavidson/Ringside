<?php

declare(strict_types=1);

namespace App\Lifecycle\Events;

use App\Data\Events\SponsorInstructionData;
use App\Enums\Events\LiveItemStatus;
use App\Enums\Events\SponsorInstructionMode;
use App\Exceptions\RecordChangedException;
use App\Exceptions\Sponsors\SponsorCommitmentException;
use App\Models\Events\EventLiveRun;
use App\Models\Events\EventPack;
use App\Models\Events\EventSponsorCommitment;
use App\Models\Matches\EventMatch;
use App\ValueObjects\PackKey;
use Illuminate\Database\Eloquent\Collection;

/**
 * A reviewed sponsor instruction can only enter the pack when nothing it was reviewed against has moved, its target
 * and wording are valid, and the live show has not started the entry it would change.
 */
final class SponsorInstructionRequirements
{
    /**
     * Refuse an instruction the current card, pack, commitment or live run does not allow. Call it inside the card lock.
     *
     * @param  Collection<int, EventMatch>  $matches
     * @param  array{hash: string, target: mixed}  $preview
     */
    public function ensureCanSave(
        EventSponsorCommitment $commitment,
        ?EventPack $pack,
        Collection $matches,
        ?EventLiveRun $run,
        array $preview,
        string $currentFingerprint,
        int $version,
        int $packVersion,
        string $fingerprint,
        SponsorInstructionData $data,
    ): void {
        if ($commitment->version !== $version || ($pack->version ?? 0) !== $packVersion || ! hash_equals($currentFingerprint, $fingerprint)
            || ! hash_equals($preview['hash'], $data->previewHash)) {
            throw RecordChangedException::changed();
        }

        if ($pack instanceof EventPack && $pack->card_fingerprint !== $fingerprint) {
            throw SponsorCommitmentException::packReviewRequired();
        }
        $targetKey = $data->targetKey instanceof PackKey ? (string) $data->targetKey : null;

        if (($data->targetKey instanceof PackKey && ($data->targetKey->kind !== $data->mode || $preview['target'] === null))
            || ($data->mode !== SponsorInstructionMode::Remove && $commitment->pack_key !== null && $commitment->pack_key !== $targetKey)) {
            throw SponsorCommitmentException::invalidTarget();
        }

        if ($data->mode === SponsorInstructionMode::Remove && ($commitment->pack_key === null || mb_trim($data->reason ?? '') === '')) {
            throw SponsorCommitmentException::reasonRequired();
        }

        if ($data->mode !== SponsorInstructionMode::Remove && (mb_trim($commitment->instruction_label ?? '') === '' || mb_trim($commitment->instruction ?? '') === '')) {
            throw SponsorCommitmentException::instructionRequired();
        }

        if ($data->afterMatchId !== null && ! $matches->contains(fn (EventMatch $match): bool => $match->id === $data->afterMatchId && ! $match->trashed())) {
            throw SponsorCommitmentException::invalidTarget();
        }
        $protectedKeys = array_filter([$commitment->pack_key, $targetKey]);

        foreach ($run->content['items'] ?? [] as $item) {
            if (in_array($item['key'], $protectedKeys, true) && $item['state'] !== LiveItemStatus::Pending->value) {
                throw SponsorCommitmentException::startedInstruction();
            }
        }
    }
}
