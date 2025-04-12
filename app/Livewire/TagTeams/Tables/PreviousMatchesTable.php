<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventMatchCompetitor;
use App\Models\TagTeam;
use App\Models\Title;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\ArrayColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class PreviousMatchesTable extends DataTableComponent
{
    use ShowTableTrait;

    /**
     * Tag Team to use for component.
     */
    public ?TagTeam $tagTeam;

    protected string $databaseTableName = 'event_matches';

    protected string $resourceName = 'matches';

    /**
     * @return Builder<EventMatch>
     */
    public function builder(): Builder
    {
        if (! isset($this->tagTeam)) {
            throw new Exception("You didn't specify a tag team");
        }

        return EventMatch::query()
            ->with(['event'])
            ->withWhereHas('competitors', function ($query) {
                $query->whereMorphedTo('competitor', $this->tagTeam);
            });
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'event_matches.event_id as event_id',
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
            LinkColumn::make(__('events.name'), 'event.name')
                ->title(fn (Event $row) => $row->name)
                ->location(fn (Event $row) => route('events.show', $row)),
            DateColumn::make(__('events.date'), 'event.date')
                ->outputFormat('Y-m-d H:i'),
            ArrayColumn::make(__('event-matches.competitors'))
                ->data(fn ($value, EventMatch $row) => ($row->competitors))
                ->outputFormat(fn ($index, EventMatchCompetitor $value) => '<a href="'.route('wrestlers.show', $value->getCompetitor()->id).'">'.$value->getCompetitor()->name.'</a>')
                ->separator('<br />'),
            ArrayColumn::make(__('event-matches.titles'))
                ->data(fn ($value, EventMatch $row) => ($row->titles))
                ->outputFormat(fn ($index, Title $value) => '<a href="'.route('titles.show', $value->id).'">'.$value->name.'</a>')
                ->separator('<br />'),
            Column::make(__('event-matches.result'))
                ->label(fn (EventMatch $row) => $row->result?->winner->name.' by '.$row->result?->decision->name),
        ];
    }
}
