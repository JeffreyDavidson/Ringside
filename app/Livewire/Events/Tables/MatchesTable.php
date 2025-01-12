<?php

declare(strict_types=1);

namespace App\Livewire\Events\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\EventMatch;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\ArrayColumn;

class MatchesTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'event_matches';

    protected string $routeBasePath = 'event-matches';

    protected string $resourceName = 'matches';

    public ?int $eventId;

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
                ->data(fn ($value, $row) => ($row->competitors))
                ->outputFormat(fn ($index, $value) => $value->competitor->name)
                ->separator(' vs '),
            ArrayColumn::make(__('event-matches.referee'))
                ->data(fn ($value, $row) => ($row->referees))
                ->outputFormat(fn ($index, $value) => $value->full_name)
                ->separator(', '),
            ArrayColumn::make(__('event-matches.title'))
                ->data(fn ($value, $row) => ($row->titles))
                ->outputFormat(fn ($index, $value) => $value->name)
                ->separator(', '),
            Column::make(__('event-matches.result'))
                ->label(
                    fn ($row, Column $column) => $row->result->winner->name.' by '.$row->result->decision->name
                ),
        ];
    }
}
