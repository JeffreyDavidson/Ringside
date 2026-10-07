<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use Throwable;

final class CannotAddStableMembersException extends BaseBusinessException
{
    public static function changedConcurrently(Throwable $previous): static
    {
        return new self(__('stables.errors.members_changed_concurrently'), previous: $previous);
    }
}
