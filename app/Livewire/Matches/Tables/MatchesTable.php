<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Tables;

use App\Builders\Matches\EventMatchBuilder;
use App\Livewire\Concerns\ShowTableTrait;
use App\Livewire\Matches\Support\MatchCompetitorRouteResolver;
use App\Livewire\Table\Column;
use App\Livewire\Table\Columns\ArrayColumn;
use App\Livewire\Table\DataTableComponent;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;

/** @extends DataTableComponent<EventMatch> */
class MatchesTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'events_matches';

    protected string $resourceName = 'matches';

    /**
     * Event to use for component.
     */
    #[Locked]
    public ?int $eventId = null;

    /**
     * @return EventMatchBuilder<EventMatch>
     */
    public function builder(): EventMatchBuilder
    {
        $eventId = $this->requireContextId($this->eventId, 'event');

        return EventMatch::query()
            ->forEventId($eventId)
            ->with(['event', 'referees', 'titles', 'competitors.competitor', 'competitors.side', 'winningSide.competitors.competitor']);
    }

    protected function configure(): void
    {
        Gate::authorize('viewAny', EventMatch::class);

        $this->addAdditionalSelects([
            'events_matches.event_id',
        ]);
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        $competitorRouteResolver = app(MatchCompetitorRouteResolver::class);

        return [
            Column::make(__('matches.match_type'), 'match_type')
                ->label(fn (EventMatch $row) => $row->match_type->label())
                ->searchable(),
            Column::make(__('matches.competitors'))
                ->label(fn (EventMatch $row): string => $row->competitors
                    ->competitorModelsBySidePosition()
                    ->map(fn (Collection $side): string => $side->map(function (Wrestler|TagTeam $competitor) use ($competitorRouteResolver): string {
                        return $competitorRouteResolver->link($competitor);
                    })->join(' & '))
                    ->join(' vs '))
                ->html(),
            ArrayColumn::make(__('matches.referees'))
                ->data(fn (EventMatch $row) => $row->referees)
                ->outputFormat(function (Referee $value): string {
                    return '<a href="'.route('referees.show', $value->id).'">'.$value->full_name.'</a>';
                })
                ->separator(', ')
                ->emptyValue('N/A'),
            ArrayColumn::make(__('matches.titles'))
                ->data(fn (EventMatch $row) => $row->titles)
                ->outputFormat(function (Title $value): string {
                    return '<a href="'.route('titles.show', $value->id).'">'.$value->name.'</a>';
                })
                ->separator(', ')
                ->emptyValue('N/A'),
            Column::make(__('matches.result'))
                ->label(
                    function (EventMatch $row, Column $column) use ($competitorRouteResolver): string {
                        if ($row->match_finish === null) {
                            return 'N/A';
                        }

                        if ($row->winningSide) {
                            $winners = $row->winningSide->competitors
                                ->map(function (MatchCompetitor $competitor) use ($competitorRouteResolver): string {
                                    $winner = $competitor->competitor;

                                    return $competitorRouteResolver->link($winner);
                                })
                                ->join(' & ');

                            return $winners.' by '.$row->match_finish->label();
                        }

                        return $row->match_finish->label();
                    }
                )->html(),
            Column::make(__('core.actions'))
                ->view('components.matches.table-result-action')
                ->html(),
        ];
    }
}
