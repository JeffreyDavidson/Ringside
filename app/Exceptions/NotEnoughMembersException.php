<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\TagTeam;
use Exception;

class NotEnoughMembersException extends Exception
{
    /**
     * The default message for sending with exception.
     *
     * @return self
     */
    public static function forTagTeam()
    {
        return new self(sprintf(
            'A tag team must contain %u wrestlers to be on a tag team.',
            TagTeam::NUMBER_OF_WRESTLERS_ON_TEAM
        ));
    }
}
