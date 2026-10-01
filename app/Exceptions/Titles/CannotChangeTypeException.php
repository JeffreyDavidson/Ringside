<?php

declare(strict_types=1);

namespace App\Exceptions\Titles;

use App\Exceptions\BaseBusinessException;
use App\Models\Titles\Title;

final class CannotChangeTypeException extends BaseBusinessException
{
    public static function hasChampionshipsOrMatches(Title $title): self
    {
        return new self("Title [{$title->name}] type cannot be changed because it has championship reigns or is booked in a match.");
    }
}
