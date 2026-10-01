<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Livewire\Support\RosterResourceRouteResolver;
use App\Models\Events\Event;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Queries\Titles\TitleChampionshipQuery;
use Illuminate\Database\Eloquent\Collection;

/**
 * Data for the promotion Overview page: what is coming up, who can be booked,
 * and who holds the titles. Queries respect the active promotion scope.
 */
final readonly class DashboardViewModel
{
    private const int UPCOMING_EVENT_LIMIT = 3;

    public function __construct(private RosterResourceRouteResolver $routeResolver) {}

    /**
     * @return Collection<int, Event>
     */
    public function upcomingEvents(): Collection
    {
        return Event::query()
            ->scheduled()
            ->orderBy('date')
            ->with('venue')
            ->withCount('matches')
            ->limit(self::UPCOMING_EVENT_LIMIT)
            ->get();
    }

    /**
     * Wrestlers under contract, split by whether they can currently be booked.
     *
     * @return array{employed: int, available: int, injured: int, suspended: int}
     */
    public function rosterAvailability(): array
    {
        return [
            'employed' => Wrestler::query()->employed()->count(),
            'available' => Wrestler::query()
                ->employed()
                ->whereDoesntHave('currentInjury')
                ->whereDoesntHave('currentSuspension')
                ->count(),
            'injured' => Wrestler::query()->employed()->whereHas('currentInjury')->count(),
            'suspended' => Wrestler::query()->employed()->whereHas('currentSuspension')->count(),
        ];
    }

    /**
     * Active titles that currently have a champion, with that reign loaded.
     *
     * @return Collection<int, Title>
     */
    public function championedTitles(): Collection
    {
        return Title::query()
            ->active()
            ->whereHas('currentChampionship')
            ->with('currentChampionship.champion')
            ->orderBy('name')
            ->get();
    }

    public function championUrl(TitleChampionship $championship): string
    {
        return $this->routeResolver->urlFor($championship->champion);
    }

    public function reignLengthInDays(TitleChampionship $championship): int
    {
        return TitleChampionshipQuery::reignLengthInDays($championship);
    }
}
