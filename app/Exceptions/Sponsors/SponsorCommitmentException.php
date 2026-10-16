<?php

declare(strict_types=1);

namespace App\Exceptions\Sponsors;

use App\Exceptions\BaseBusinessException;

final class SponsorCommitmentException extends BaseBusinessException
{
    public static function archived(): self
    {
        return new self(__('sponsors.archived'));
    }

    public static function eventUnavailable(): self
    {
        return new self(__('sponsors.event_unavailable'));
    }

    public static function evidenceRequired(): self
    {
        return new self(__('sponsors.evidence_required'));
    }

    public static function instructionRequired(): self
    {
        return new self(__('sponsors.instruction_required'));
    }

    public static function invalidTarget(): self
    {
        return new self(__('sponsors.invalid_target'));
    }

    public static function packReviewRequired(): self
    {
        return new self(__('sponsors.pack_review_required'));
    }

    public static function reasonRequired(): self
    {
        return new self(__('sponsors.reason_required'));
    }

    public static function segmentLimit(): self
    {
        return new self(__('sponsors.segment_limit'));
    }

    public static function startedInstruction(): self
    {
        return new self(__('sponsors.started_instruction'));
    }

    public static function usedTarget(): self
    {
        return new self(__('sponsors.used_target'));
    }
}
