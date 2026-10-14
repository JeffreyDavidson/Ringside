<?php

declare(strict_types=1);

namespace App\Exceptions\Crew;

use App\Exceptions\BaseBusinessException;

final class CrewConflictException extends BaseBusinessException
{
    public static function stale(): self
    {
        return new self(__('production.contact_conflict'));
    }
}
