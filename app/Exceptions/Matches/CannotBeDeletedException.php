<?php

declare(strict_types=1);

namespace App\Exceptions\Matches;

use App\Exceptions\BaseBusinessException;

final class CannotBeDeletedException extends BaseBusinessException
{
    public static function hasTitleChange(): self
    {
        return new self(__('matches.errors.deleted.has_title_change'));
    }
}
