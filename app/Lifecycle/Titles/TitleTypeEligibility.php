<?php

declare(strict_types=1);

namespace App\Lifecycle\Titles;

use App\Enums\Titles\TitleType;
use App\Exceptions\Titles\CannotChangeTypeException;
use App\Models\Matches\EventMatch;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Builder;

final class TitleTypeEligibility
{
    /** A title's type is fixed once it has any championship reign or is booked in a match. */
    public static function isLocked(Title $title): bool
    {
        return $title->championships()->exists()
            || EventMatch::query()
                ->whereHas('titles', fn (Builder $query): Builder => $query->whereKey($title->id))
                ->exists();
    }

    public static function ensureCanChange(Title $title, TitleType $targetType): void
    {
        if ($title->type === $targetType) {
            return;
        }

        if (self::isLocked($title)) {
            throw CannotChangeTypeException::hasChampionshipsOrMatches($title);
        }
    }
}
