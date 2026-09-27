<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Stables;

final class StableMembershipRequirements
{
    public const int MINIMUM_MEMBER_COUNT = 3;

    public static function hasMinimumHeadcount(int $headcount): bool
    {
        return $headcount >= self::MINIMUM_MEMBER_COUNT;
    }
}
