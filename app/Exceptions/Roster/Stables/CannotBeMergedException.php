<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Stables\Stable;

final class CannotBeMergedException extends BaseBusinessException
{
    public static function selfMerge(Stable $stable): static
    {
        return self::forModel('stables.errors.merged.self_merge', $stable);
    }

    public static function differentPromotions(Stable $primaryStable, Stable $secondaryStable): static
    {
        $primaryContext = self::formatModelContext($primaryStable);
        $secondaryContext = self::formatModelContext($secondaryStable);

        return new self(__('stables.errors.merged.different_promotions', ['primary_context' => $primaryContext, 'secondary_context' => $secondaryContext]));
    }

    public static function primaryRetired(Stable $primaryStable): static
    {
        return self::forModel('stables.errors.merged.primary_retired', $primaryStable);
    }

    public static function secondaryRetired(Stable $secondaryStable): static
    {
        return self::forModel('stables.errors.merged.secondary_retired', $secondaryStable);
    }

    public static function primaryNotActive(Stable $primaryStable): static
    {
        return self::forModel('stables.errors.merged.primary_not_active', $primaryStable);
    }

    public static function secondaryNotActive(Stable $secondaryStable): static
    {
        return self::forModel('stables.errors.merged.secondary_not_active', $secondaryStable);
    }

    /** @param array<int, string> $memberNames */
    public static function membersUnavailable(array $memberNames): static
    {
        return new self(__('stables.errors.merged.members_unavailable', ['members' => implode(', ', $memberNames)]));
    }
}
