<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Support;

use App\Livewire\Support\RosterResourceRouteResolver;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

final readonly class MatchCompetitorRouteResolver
{
    public function __construct(private RosterResourceRouteResolver $routeResolver) {}

    public function link(Wrestler|TagTeam $competitor, bool $unbookable = false): string
    {
        $marker = $unbookable ? self::unbookableMarker() : '';

        if ($competitor->trashed()) {
            return e($competitor->name).$marker;
        }

        return '<a href="'.e($this->routeResolver->urlFor($competitor)).'">'.e($competitor->name).'</a>'.$marker;
    }

    /**
     * Small warning shown next to a booked competitor or referee who can no longer be booked.
     */
    public static function unbookableMarker(): string
    {
        return ' <span class="text-2xs font-medium text-ringside-warning">('.e(__('matches.no_longer_bookable')).')</span>';
    }
}
