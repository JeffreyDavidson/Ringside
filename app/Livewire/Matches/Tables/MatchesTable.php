<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Tables;

use App\Builders\Matches\EventMatchBuilder;
use App\Livewire\Concerns\ShowTableTrait;
use App\Livewire\Matches\Support\MatchTableFormatter;
use App\Livewire\Table\Column;
use App\Livewire\Table\Columns\ArrayColumn;
use App\Livewire\Table\DataTableComponent;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use App\Models\Titles\Title;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;

/** @extends DataTableComponent<EventMatch> */
class MatchesTable extends DataTableComponent
{
    use ShowTableTrait;

    protected MatchTableFormatter $matchTableFormatter;

    protected string $databaseTableName = 'events_matches';

    protected string $resourceName = 'matches';

    /**
     * Event to use for component.
     */
    #[Locked]
    public ?int $eventId = null;

    public function boot(MatchTableFormatter $matchTableFormatter): void
    {
        $this->matchTableFormatter = $matchTableFormatter;
    }

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
        $eventId = $this->requireContextId($this->eventId, 'event');

        Gate::authorize('view', Event::query()->findOrFail($eventId));

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
                ->label(fn (EventMatch $row): string => $this->matchTableFormatter->competitorLinks($row))
                ->html(),
            ArrayColumn::make(__('matches.referees'))
                ->data(fn (EventMatch $row) => $row->referees)
                ->link(
                    title: fn (Referee $value): string => $value->full_name,
                    location: fn (Referee $value): string => route('referees.show', $value->id),
                )
                ->separator(', ')
                ->emptyValue('N/A'),
            ArrayColumn::make(__('matches.titles'))
                ->data(fn (EventMatch $row) => $row->titles)
                ->link(
                    title: fn (Title $value): string => $value->name,
                    location: fn (Title $value): string => route('titles.show', $value->id),
                )
                ->separator(', ')
                ->emptyValue('N/A'),
            Column::make(__('matches.result'))
                ->label(fn (EventMatch $row): string => $this->matchTableFormatter->result($row))
                ->html(),
            Column::make(__('core.actions'))
                ->view('components.matches.table-result-action')
                ->html(),
        ];
    }
}
