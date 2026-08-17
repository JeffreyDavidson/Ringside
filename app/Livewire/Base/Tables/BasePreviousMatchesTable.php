<?php

declare(strict_types=1);

namespace App\Livewire\Base\Tables;

use App\Livewire\Concerns\ShowTableTrait;
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

    public function configure(): void
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
        return [
            LinkColumn::make(__('events.name'), 'event.name')
                ->title(fn (EventMatch $row) => $row->event->name)
                ->location(fn (EventMatch $row) => route('events.show', $row->event)),
            DateColumn::make(__('events.date'), 'event.date')
                ->inputFormat('Y-m-d H:i:s')
                ->outputFormat('Y-m-d')
                ->emptyValue('N/A'),
            ArrayColumn::make(__('event-matches.referees'))
                ->data(fn (mixed $value, EventMatch $row) => ($row->referees))
                ->outputFormat(function (int $index, Referee $value): string {
                    return '<a href="'.route('referees.show', $value->id).'">'.$value->full_name.'</a>';
                })
                ->separator(', ')
                ->emptyValue('N/A'),
            Column::make(__('event-matches.competitors'))
                ->label(fn (EventMatch $row): string => $row->competitors
                    ->competitorModelsBySidePosition()
                    ->map(fn (Collection $side): string => $side->map(function (Wrestler|TagTeam $competitor): string {
                        $type = str($competitor->getMorphClass())->kebab()->plural();

                        return '<a href="'.route($type.'.show', $competitor->id).'">'.$competitor->name.'</a>';
                    })->join(' & '))
                    ->join(' vs '))
                ->html(),
            ArrayColumn::make(__('event-matches.titles'))
                ->data(fn (mixed $value, EventMatch $row) => ($row->titles))
                ->outputFormat(fn (int $index, Title $value): string => '<a href="'.route('titles.show', $value->id).'">'.$value->name.'</a>')
                ->separator('<br />')
                ->emptyValue('N/A'),
            Column::make(__('event-matches.result'))
                ->label(function (EventMatch $row): string {
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
                })->html(),
        ];
    }
}
