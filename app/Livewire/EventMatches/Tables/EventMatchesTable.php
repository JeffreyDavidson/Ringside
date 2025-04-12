<?php

declare(strict_types=1);

namespace App\Livewire\EventMatches\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\EventMatch;
use App\Models\EventMatchCompetitor;
use App\Models\Referee;
use App\Models\Title;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\ArrayColumn;

class EventMatchesTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'events_matches';

    protected string $routeBasePath = 'event-matches';

    protected string $resourceName = 'matches';

    public ?int $eventId;

    /**
     * @return Builder<EventMatch>
     */
    public function builder(): Builder
    {
        if (! isset($this->eventId)) {
            throw new \Exception("You didn't specify a event");
        }

        return EventMatch::query()
            ->with(['event', 'titles', 'competitors', 'result.winner', 'result.decision'])
            ->where('event_id', $this->eventId);
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'events_matches.event_id',
        ]);
    }

    /**
     * Undocumented function
     *
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make(__('event-matches.match_type'), 'matchType.name'),
            ArrayColumn::make(__('event-matches.competitors'))
                ->data(fn ($value, EventMatch $row) => ($row->competitors))
                ->outputFormat(function ($index, EventMatchCompetitor $value) {
                    $competitor = $value->getCompetitor();
                    $type = str($competitor->getMorphClass())->kebab()->plural();

                    return '<a href="'.route($type.'.show', $competitor->id).'">'.$competitor->name.'</a>';
                })
                ->separator(' vs '),
            ArrayColumn::make(__('event-matches.referees'))
                ->data(fn ($value, EventMatch $row) => ($row->referees))
                ->outputFormat(function ($index, Referee $value) {
                    return '<a href="'.route('referees.show', $value->id).'">'.$value->full_name.'</a>';
                })
                ->separator(', ')
                ->emptyValue('N/A'),
            ArrayColumn::make(__('event-matches.titles'))
                ->data(fn ($value, EventMatch $row) => ($row->titles))
                ->outputFormat(function ($index, Title $value) {
                    return '<a href="'.route('titles.show', $value->id).'">'.$value->name.'</a>';
                })
                ->separator(', ')
                ->emptyValue('N/A'),
            Column::make(__('event-matches.result'))
                ->label(
                    function (EventMatch $row, Column $column) {
                        $winner = $row->result?->getWinner();

                        if ($winner) {
                            $type = str($winner->getMorphClass())->kebab()->plural();

                            return '<a href="'.route($type.'.show', $winner->id).'">'.$winner->name.'</a> by '.$row->result?->decision->name;
                        }

                        return 'N/A';
                    }
                )->html(),
        ];
    }
}
