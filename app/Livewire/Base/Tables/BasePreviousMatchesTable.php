<?php

declare(strict_types=1);

namespace App\Livewire\Base\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Livewire\Matches\Support\MatchTableFormatter;
use App\Livewire\Table\Column;
use App\Livewire\Table\Columns\ArrayColumn;
use App\Livewire\Table\Columns\DateColumn;
use App\Livewire\Table\Columns\LinkColumn;
use App\Livewire\Table\DataTableComponent;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use App\Models\Titles\Title;

/**
 * @extends DataTableComponent<EventMatch>
 */
abstract class BasePreviousMatchesTable extends DataTableComponent
{
    use ShowTableTrait;

    protected MatchTableFormatter $matchTableFormatter;

    protected string $databaseTableName = 'events_matches';

    protected string $resourceName = 'matches';

    public function boot(MatchTableFormatter $matchTableFormatter): void
    {
        $this->matchTableFormatter = $matchTableFormatter;
    }

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
                ->label(fn (EventMatch $row): string => $this->matchTableFormatter->competitorLinks($row))
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
                ->label(fn (EventMatch $row): string => $this->matchTableFormatter->result($row))
                ->html(),
        ];
    }
}
