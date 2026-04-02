<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Livewire\Table\Column;
use App\Livewire\Table\Columns\ArrayColumn;
use App\Livewire\Table\DataTableComponent;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Referees\Referee;
use App\Models\Titles\Title;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

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
            throw new Exception("You didn't specify a event");
        }

        return EventMatch::query()
            ->with(['event', 'titles', 'competitors', 'result.winner', 'result.decision'])
            ->where('event_id', $this->eventId);
    }

    public function configure(): void
    {
        Gate::authorize('viewList', EventMatch::class);

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
            Column::make(__('matches.match_type'), 'matchType.name')->searchable(),
            ArrayColumn::make(__('matches.competitors'))
                ->data(fn (mixed $value, EventMatch $row) => ($row->competitors))
                ->outputFormat(function (int $index, MatchCompetitor $value): string {
                    $competitor = $value->getCompetitor();
                    $type = str($competitor->getMorphClass())->kebab()->plural();

                    return '<a href="'.route($type.'.show', $competitor->id).'">'.$competitor->name.'</a>';
                })
                ->separator(' vs '),
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
                        $winner = $row->result?->winner;

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
