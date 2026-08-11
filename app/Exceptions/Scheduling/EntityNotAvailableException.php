<?php

declare(strict_types=1);

namespace App\Exceptions\Scheduling;

use App\Exceptions\BaseBusinessException;

final class EntityNotAvailableException extends BaseBusinessException
{
    public static function forMatchAssignment(string $entityType): static
    {
        return new self("No eligible {$entityType} were provided for match assignment.");
    }
}
