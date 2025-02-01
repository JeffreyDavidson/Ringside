<?php

declare(strict_types=1);

namespace App\Livewire\Referees\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventMatchCompetitor;
use App\Models\Referee;
use App\Models\Title;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\ArrayColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class PreviousMatchesTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'event_matches';

    protected string $resourceName = 'matches';

    /**
     * Referee to use for component.
     */
    public Referee $referee;

    /**
     * Set the Referee to be used for this component.
     */
    public function mount(Referee $referee): void
    {
        $this->referee = $referee;
    }

    /**
     * @return Builder<EventMatch>
     */
    public function builder(): Builder
    {
        return EventMatch::query()
            ->with(['event', 'titles', 'competitors', 'result.winner', 'result.decision'])
            ->whereHas('referees', function ($query) {
                $query->whereIn('referee_id', [$this->referee->id]);
            });
    }

    public function configure(): void {}

    /**
     * Undocumented function
     *
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            LinkColumn::make(__('event-matches.event'))
                ->title(fn (EventMatch $row) => $row->event->name)
                ->location(fn (Event $row) => route('events.show', $row)),
            DateColumn::make(__('event-matches.date'), 'event.date')
                ->outputFormat('Y-m-d H:i'),
            ArrayColumn::make(__('event-matches.competitors'))
                ->data(fn ($value, EventMatch $row) => ($row->competitors))
                ->outputFormat(fn ($index, EventMatchCompetitor $value) => '<a href="'.route('wrestlers.show', $value->competitor->id).'">'.$value->competitor->name.'</a>')
                ->separator('<br />'),
            ArrayColumn::make(__('event-matches.titles'))
                ->data(fn ($value, EventMatch $row) => ($row->titles))
                ->outputFormat(fn ($index, Title $value) => '<a href="'.route('titles.show', $value->id).'">'.$value->name.'</a>')
                ->separator('<br />'),
            Column::make(__('event-matches.result'))
                ->label(fn (EventMatch $row) => $row->result->winner->name.' by '.$row->result->decision->name),
        ];
    }
}
