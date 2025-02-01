<?php

declare(strict_types=1);

namespace App\Livewire\EventMatches\Tables;

use App\Livewire\Base\Tables\BaseTableWithActions;
use App\Models\EventMatch;
use App\Models\EventMatchCompetitor;
use App\Models\Referee;
use App\Models\Title;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\ArrayColumn;

class EventMatchesTable extends BaseTableWithActions
{
    protected string $databaseTableName = 'event_matches';

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
            ->where('event_id', $this->eventId);
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
            Column::make(__('event-matches.match_type'), 'matchType.name'),
            ArrayColumn::make(__('event-matches.competitors'))
                ->data(fn ($value, EventMatch $row) => ($row->competitors))
                ->outputFormat(fn ($index, EventMatchCompetitor $value) => $value->competitor->name)
                ->separator(' vs '),
            ArrayColumn::make(__('event-matches.referee'))
                ->data(fn ($value, EventMatch $row) => ($row->referees))
                ->outputFormat(fn ($index, Referee $value) => $value->full_name)
                ->separator(', '),
            ArrayColumn::make(__('event-matches.title'))
                ->data(fn ($value, EventMatch $row) => ($row->titles))
                ->outputFormat(fn ($index, Title $value) => $value->name)
                ->separator(', '),
            Column::make(__('event-matches.result'))
                ->label(
                    fn (EventMatch $row, Column $column) => $row->result->winner->name.' by '.$row->result->decision->name
                ),
        ];
    }
}
