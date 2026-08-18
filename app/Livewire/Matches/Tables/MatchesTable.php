<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Livewire\Table\Column;
use App\Livewire\Table\Columns\ArrayColumn;
use App\Livewire\Table\DataTableComponent;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use LogicException;

/** @extends DataTableComponent<EventMatch> */
class MatchesTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'events_matches';

    protected string $resourceName = 'matches';

    /**
     * Event to use for component.
     */
    public ?int $eventId = null;

    /**
     * @return Builder<EventMatch>
     */
    public function builder(): Builder
    {
        if ($this->eventId === null) {
            throw new LogicException('An event was not provided.');
        }

        return EventMatch::query()
            ->with(['event', 'titles', 'competitors.competitor', 'competitors.side', 'winningSide.competitors.competitor'])
            ->where('event_id', $this->eventId);
    }

    public function configure(): void
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
        return [
            Column::make(__('matches.match_type'), 'match_type')
                ->label(fn (EventMatch $row) => $row->match_type->label())
                ->searchable(),
            Column::make(__('matches.competitors'))
                ->label(fn (EventMatch $row): string => $row->competitors
                    ->competitorModelsBySidePosition()
                    ->map(fn (Collection $side): string => $side->map(function (Wrestler|TagTeam $competitor): string {
                        $routeName = $competitor instanceof Wrestler
                            ? 'wrestlers.show'
                            : 'tag-teams.show';

                        return '<a href="'.route($routeName, $competitor).'">'.$competitor->name.'</a>';
                    })->join(' & '))
                    ->join(' vs '))
                ->html(),
            ArrayColumn::make(__('matches.referees'))
                ->data(fn (mixed $value, EventMatch $row) => ($row->referees))
                ->outputFormat(function (int $index, Referee $value): string {
                    return '<a href="'.route('referees.show', $value->id).'">'.$value->full_name.'</a>';
                })
                ->separator(', ')
                ->emptyValue('N/A'),
            ArrayColumn::make(__('matches.titles'))
                ->data(fn (mixed $value, EventMatch $row) => ($row->titles))
                ->outputFormat(function (int $index, Title $value): string {
                    return '<a href="'.route('titles.show', $value->id).'">'.$value->name.'</a>';
                })
                ->separator(', ')
                ->emptyValue('N/A'),
            Column::make(__('matches.result'))
                ->label(
                    function (EventMatch $row, Column $column): string {
                        if ($row->match_finish === null) {
                            return 'N/A';
                        }

                        if ($row->winningSide) {
                            $winners = $row->winningSide->competitors
                                ->map(function (MatchCompetitor $competitor): string {
                                    $winner = $competitor->competitor;
                                    $type = str($winner->getMorphClass())->kebab()->plural();

                                    return '<a href="'.route($type.'.show', $winner->id).'">'.$winner->name.'</a>';
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
