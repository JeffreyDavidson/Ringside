<?php

declare(strict_types=1);

namespace App\Lifecycle\Events;

use App\Enums\Events\SponsorCommitmentStatus;
use App\Exceptions\Sponsors\SponsorCommitmentException;
use App\Models\Events\EventSponsorCommitment;
use Carbon\CarbonInterface;

/**
 * The moves a sponsor promise may make between its three stored statuses.
 *
 * It reads the promise and refuses with SponsorCommitmentException; it never locks or writes.
 * SaveSponsorCommitmentAction locks the promise and saves it. A promise that does not exist yet starts from Pending.
 */
final readonly class SponsorCommitmentStateMachine
{
    /**
     * The allowed moves, by the status the promise is in (the key is the status value). Anything not listed is refused.
     * Every move is allowed: a promise may be saved again, completed, cancelled or reopened. What a move costs is a reason
     * or evidence, which requiresReason() and ensureCanComplete() decide.
     */
    private const array MOVES = [
        'pending' => [SponsorCommitmentStatus::Pending, SponsorCommitmentStatus::Completed, SponsorCommitmentStatus::Cancelled],
        'completed' => [SponsorCommitmentStatus::Pending, SponsorCommitmentStatus::Completed, SponsorCommitmentStatus::Cancelled],
        'cancelled' => [SponsorCommitmentStatus::Pending, SponsorCommitmentStatus::Completed, SponsorCommitmentStatus::Cancelled],
    ];

    public function canMove(SponsorCommitmentStatus $from, SponsorCommitmentStatus $to): bool
    {
        return in_array($to, self::MOVES[$from->value], true);
    }

    /** Cancelling, and any save of a promise that is already completed or cancelled (a correction or a reopening), must say why. */
    public function requiresReason(SponsorCommitmentStatus $from, SponsorCommitmentStatus $to): bool
    {
        return $to === SponsorCommitmentStatus::Cancelled || $from !== SponsorCommitmentStatus::Pending;
    }

    /**
     * Refuse a move that needs a reason and has none. The table allows every move, so it is not checked here.
     *
     * @throws SponsorCommitmentException
     */
    public function ensureCanMove(?EventSponsorCommitment $commitment, SponsorCommitmentStatus $to, ?string $reason): void
    {
        $from = $commitment->status ?? SponsorCommitmentStatus::Pending;

        if ($this->requiresReason($from, $to) && mb_trim($reason ?? '') === '') {
            throw SponsorCommitmentException::reasonRequired();
        }
    }

    /**
     * Refuse a completion with no evidence written down, or one dated in the future.
     *
     * @throws SponsorCommitmentException
     */
    public function ensureCanComplete(SponsorCommitmentStatus $to, ?string $evidence, ?CarbonInterface $completedAt): void
    {
        if ($to !== SponsorCommitmentStatus::Completed) {
            return;
        }

        if (mb_trim($evidence ?? '') === '' || $completedAt?->isFuture()) {
            throw SponsorCommitmentException::evidenceRequired();
        }
    }
}
