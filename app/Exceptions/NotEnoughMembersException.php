<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\TagTeam;
use Exception;

class NotEnoughMembersException extends Exception
{
    /**
     * The default message for sending with exception.
     */
    public static function forTagTeam(): self
    {
        return new self(sprintf(
            'A tag team must contain %u wrestlers to be on a tag team.',
            TagTeam::NUMBER_OF_WRESTLERS_ON_TEAM
        ));
    }
}
