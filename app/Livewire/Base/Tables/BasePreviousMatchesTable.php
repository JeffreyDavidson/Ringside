<?php

declare(strict_types=1);

namespace App\Livewire\Base\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Livewire\Matches\Support\MatchCompetitorRouteResolver;
use App\Livewire\Table\Column;
use App\Livewire\Table\Columns\ArrayColumn;
use App\Livewire\Table\Columns\DateColumn;
use App\Livewire\Table\Columns\LinkColumn;
use App\Livewire\Table\DataTableComponent;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Support\Collection;

/**
 * @extends DataTableComponent<EventMatch>
 */
abstract class BasePreviousMatchesTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'events_matches';

    protected string $resourceName = 'matches';

    protected function configure(): void
    {
        $this->addAdditionalSelects([
            'events_matches.event_id as event_id',
        ]);
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        $competitorRouteResolver = app(MatchCompetitorRouteResolver::class);

        return [
            LinkColumn::make(__('events.name'), 'event.name')
                ->title(fn (EventMatch $row) => $row->event->name)
                ->location(fn (EventMatch $row): string => route('events.show', $row->event)),
            DateColumn::make(__('events.date'), 'event.date')
                ->inputFormat('Y-m-d H:i:s')
                ->outputFormat('Y-m-d')
                ->emptyValue('N/A'),
            ArrayColumn::make(__('event-matches.referees'))
                ->data(fn (EventMatch $row) => $row->referees)
                ->link(
                    title: fn (Referee $value): string => $value->full_name,
                    location: fn (Referee $value): string => route('referees.show', $value->id),
                )
                ->separator(', ')
                ->emptyValue('N/A'),
            Column::make(__('event-matches.competitors'))
                ->label(fn (EventMatch $row): string => $row->competitors
                    ->competitorModelsBySidePosition()
                    ->map(fn (Collection $side): string => $side
                        ->map(fn (Wrestler|TagTeam $competitor): string => $competitorRouteResolver->link($competitor))
                        ->join(' & '))
                    ->join(' vs '))
                ->html(),
            ArrayColumn::make(__('event-matches.titles'))
                ->data(fn (EventMatch $row) => $row->titles)
                ->link(
                    title: fn (Title $value): string => $value->name,
                    location: fn (Title $value): string => route('titles.show', $value->id),
                )
                ->separator('<br />')
                ->emptyValue('N/A'),
            Column::make(__('event-matches.result'))
                ->label(function (EventMatch $row) use ($competitorRouteResolver): string {
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
                })->html(),
        ];
    }
}
