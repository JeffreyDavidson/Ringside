<?php

declare(strict_types=1);

namespace App\Lifecycle\Events;

use App\Enums\EventStatus;
use App\Exceptions\Events\CannotBeRescheduledException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Titles\TitleChampionship;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

final class EventSchedulingEligibility
{
    public static function ensureDateCanChange(Event $event, ?Carbon $targetDate): void
    {
        if (! self::isDateChanging($event, $targetDate)) {
            return;
        }

        if ($event->status === EventStatus::Past) {
            throw CannotBeRescheduledException::alreadyOccurred($event);
        }

        if (self::hasTitleReigns($event)) {
            throw CannotBeRescheduledException::hasTitleReigns($event);
        }
    }

    /** Reigns store the event date as their start or end, so the date is fixed once a match has created or closed one. */
    private static function hasTitleReigns(Event $event): bool
    {
        $matchIds = $event->matches()->withTrashed()->select((new EventMatch)->qualifyColumn('id'));

        return TitleChampionship::query()
            ->where(fn (Builder $query): Builder => $query
                ->whereIn('won_match_id', $matchIds)
                ->orWhereIn('lost_match_id', $matchIds))
            ->exists();
    }

    public static function isDateChanging(Event $event, ?Carbon $targetDate): bool
    {
        if ($event->date === null) {
            return $targetDate instanceof Carbon;
        }

        return ! $targetDate instanceof Carbon || ! $event->date->isSameSecond($targetDate);
    }
}
