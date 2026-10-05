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
use Illuminate\Support\Facades\DB;

/**
 * Data for the promotion Overview page: what is coming up, who can be booked,
 * and who holds the titles. Queries respect the active promotion scope; the dashboard route runs inside the
 * `promotion.context` middleware, so users without an active membership never reach this class.
 */
final readonly class DashboardViewModel
{
    private const int UPCOMING_EVENT_LIMIT = 3;

    private const int CHAMPION_LIMIT = 12;

    public function __construct(private RosterResourceRouteResolver $routeResolver) {}

    /**
     * @return Collection<int, Event>
     */
    public function upcomingEvents(): Collection
    {
        return Event::query()
            ->scheduled()
            ->orderBy('date')
            ->with(['venue', 'promotion'])
            ->withCount('matches')
            ->limit(self::UPCOMING_EVENT_LIMIT)
            ->get();
    }

    /**
     * Wrestlers under contract, split by whether they can currently be booked.
     *
     * The conditional counts use count(case ... end) rather than PostgreSQL's count(*) filter (where ...), which
     * MySQL does not support. The injured and suspended flags are booleans on PostgreSQL and 0/1 on MySQL and
     * SQLite; a bare flag in a CASE condition reads correctly on all three. The derived table selects only the id
     * and the two flags, so the outer query does not carry every wrestler column through.
     *
     * @return array{employed: int, available: int, injured: int, suspended: int}
     */
    public function rosterAvailability(): array
    {
        $counts = DB::query()
            ->fromSub(
                Wrestler::query()->select('wrestlers.id')->employed()->withExists([
                    'currentInjury as injured',
                    'currentSuspension as suspended',
                ]),
                'employed_wrestlers',
            )
            ->selectRaw('count(*) as employed')
            ->selectRaw('count(case when not injured and not suspended then 1 end) as available')
            ->selectRaw('count(case when injured then 1 end) as injured')
            ->selectRaw('count(case when suspended then 1 end) as suspended')
            ->sole();

        return [
            'employed' => (int) $counts->employed,
            'available' => (int) $counts->available,
            'injured' => (int) $counts->injured,
            'suspended' => (int) $counts->suspended,
        ];
    }

    /**
     * The first titles by name that are active and currently have a champion, with that reign loaded.
     *
     * @return Collection<int, Title>
     */
    public function championedTitles(): Collection
    {
        return Title::query()
            ->active()
            ->whereHas('currentChampionship')
            ->with(['currentChampionship.champion', 'currentChampionship.title.promotion'])
            ->orderBy('name')
            ->limit(self::CHAMPION_LIMIT)
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
