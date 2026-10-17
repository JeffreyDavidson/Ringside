<?php

declare(strict_types=1);

namespace App\Exceptions\Events;

use App\Exceptions\BaseBusinessException;

final class BudgetException extends BaseBusinessException
{
    public static function alreadyVoided(): self
    {
        return new self(__('event-budget.already_voided'));
    }

    public static function archived(): self
    {
        return new self(__('event-budget.archived'));
    }

    public static function cannotArchive(): self
    {
        return new self(__('event-budget.cannot_archive'));
    }

    public static function categoryDirection(): self
    {
        return new self(__('event-budget.category_direction'));
    }

    public static function dependentRefunds(): self
    {
        return new self(__('event-budget.dependent_refunds'));
    }

    public static function directionLocked(): self
    {
        return new self(__('event-budget.direction_locked'));
    }

    public static function excess(): self
    {
        return new self(__('event-budget.excess'));
    }

    public static function keyConflict(): self
    {
        return new self(__('event-budget.key_conflict'));
    }

    public static function lineLimit(): self
    {
        return new self(__('event-budget.line_limit'));
    }

    public static function movementMissing(): self
    {
        return new self(__('event-budget.movement_missing'));
    }

    public static function movementLimit(): self
    {
        return new self(__('event-budget.movement_limit'));
    }

    public static function paymentDate(): self
    {
        return new self(__('event-budget.payment_date'));
    }

    public static function positiveAmount(): self
    {
        return new self(__('event-budget.positive_amount'));
    }

    public static function reasonRequired(): self
    {
        return new self(__('event-budget.reason_required'));
    }

    public static function refundCap(): self
    {
        return new self(__('event-budget.refund_cap'));
    }

    public static function refundSource(): self
    {
        return new self(__('event-budget.refund_source'));
    }
}
