<?php

declare(strict_types=1);

namespace App\Exceptions\Titles;

use App\Exceptions\BaseBusinessException;
use App\Models\Titles\Title;

final class CannotChangeTypeException extends BaseBusinessException
{
    public static function hasChampionshipsOrMatches(Title $title): self
    {
        return new self(__('titles.errors.change_type.has_championships_or_matches', ['title' => $title->name]));
    }
}
