<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Stables\Stable;

final class CannotBeEstablishedException extends BaseBusinessException
{
    public static function deleted(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.established.deleted', ['context' => $context]));
    }

    public static function established(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.established.established', ['context' => $context]));
    }

    public static function insufficientMembers(Stable $stable, int $currentMembers, int $minimumMembers): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.established.insufficient_members', ['context' => $context, 'current_members' => $currentMembers, 'minimum_members' => $minimumMembers]));
    }

    public static function withEndDate(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.established.with_end_date', ['context' => $context]));
    }
}
