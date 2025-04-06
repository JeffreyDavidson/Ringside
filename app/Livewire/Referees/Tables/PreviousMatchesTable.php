<?php

declare(strict_types=1);

namespace App\Livewire\Referees\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\EventMatch;
use App\Models\EventMatchCompetitor;
use App\Models\Title;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\ArrayColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class PreviousMatchesTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'events_matches';

    protected string $resourceName = 'matches';

    /**
     * Referee to use for component.
     */
    public ?int $refereeId;

    /**
     * @return Builder<EventMatch>
     */
    public function builder(): Builder
    {
        if (! isset($this->refereeId)) {
            throw new \Exception("You didn't specify a referee");
        }

        return EventMatch::query()
            ->with(['event', 'titles', 'competitors', 'result.winner', 'result.decision'])
            ->whereHas('referees', function (Builder $query) {
                $query->whereIn('referee_id', [$this->refereeId]);
            });
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
            LinkColumn::make(__('events.name'))
                ->title(fn (Model $row) => $row->event->name)
                ->location(fn (Model $row) => route('events.show', $row)),
            DateColumn::make(__('events.date'), 'event.date')
                ->outputFormat('Y-m-d H:i'),
            ArrayColumn::make(__('event-matches.competitors'))
                ->data(fn ($value, EventMatch $row) => ($row->competitors))
                ->outputFormat(function ($index, EventMatchCompetitor $value) {
                    $competitor = $value->getCompetitor();
                    $type = str($competitor->getMorphClass())->kebab()->plural();

                    return '<a href="'.route($type.'.show', $competitor->id).'">'.$competitor->name.'</a>';
                })
                ->separator('<br />'),
            ArrayColumn::make(__('event-matches.titles'))
                ->data(fn ($value, EventMatch $row) => ($row->titles))
                ->outputFormat(fn ($index, Title $value) => '<a href="'.route('titles.show', $value->id).'">'.$value->name.'</a>')
                ->separator('<br />')
                ->emptyValue('N/A'),
            Column::make(__('event-matches.result'))
                ->label(fn (EventMatch $row) => $row->result?->winner->name.' by '.$row->result?->decision->name),
        ];
    }
}
