<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\Matches\EventMatch;
use App\Models\Matches\EventMatchCompetitor;
use App\Models\Referees\Referee;
use App\Models\Titles\Title;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\ArrayColumn;

class EventMatchesTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'events_matches';

    protected string $resourceName = 'matches';

    /**
     * Event to use for component.
     */
    public ?int $eventId;

    /**
     * @return Builder<EventMatch>
     */
    public function builder(): Builder
    {
        if ($this->eventId === null) {
            throw new Exception("You didn't specify a event");
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
                ->data(fn (mixed $value, EventMatch $row) => ($row->competitors))
                ->outputFormat(function (int $index, EventMatchCompetitor $value): string {
                    $competitor = $value->getCompetitor();
                    $type = str($competitor->getMorphClass())->kebab()->plural();

                    return '<a href="'.route($type.'.show', $competitor->id).'">'.$competitor->name.'</a>';
                })
                ->separator(' vs '),
            ArrayColumn::make(__('event-matches.referees'))
                ->data(fn (mixed $value, EventMatch $row) => ($row->referees))
                ->outputFormat(function (int $index, Referee $value): string {
                    return '<a href="'.route('referees.show', $value->id).'">'.$value->full_name.'</a>';
                })
                ->separator(', ')
                ->emptyValue('N/A'),
            ArrayColumn::make(__('event-matches.titles'))
                ->data(fn (mixed $value, EventMatch $row) => ($row->titles))
                ->outputFormat(function (int $index, Title $value): string {
                    return '<a href="'.route('titles.show', $value->id).'">'.$value->name.'</a>';
                })
                ->separator(', ')
                ->emptyValue('N/A'),
            Column::make(__('event-matches.result'))
                ->label(
                    function (EventMatch $row, Column $column): string {
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
