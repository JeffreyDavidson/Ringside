<?php

declare(strict_types=1);

namespace App\Exceptions\Storylines;

use App\Exceptions\BaseBusinessException;

final class StorylineConflictException extends BaseBusinessException
{
    public static function state(): self
    {
        return new self(__('storylines.state_error'));
    }

    public static function source(): self
    {
        return new self(__('storylines.source_changed'));
    }
}
