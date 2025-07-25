<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Tables;

use App\Livewire\Base\Tables\BaseTable;
use App\Models\Matches\EventMatch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class Main extends BaseTable
{
    protected bool $showActionColumn = false;

    protected string $databaseTableName = 'events_matches';

    protected string $routeBasePath = 'matches';

    protected string $resourceName = 'matches';

    /**
     * @return Builder<EventMatch>
     */
    public function builder(): Builder
    {
        return EventMatch::query()
            ->with(['event', 'matchType', 'competitors', 'result.winner', 'result.decision'])
            ->orderBy('events_matches.created_at', 'desc');
    }

    public function configure(): void
    {
        Gate::authorize('viewList', EventMatch::class);

        $this->addAdditionalSelects([
            'events_matches.event_id',
            'events_matches.match_type_id',
        ]);
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            LinkColumn::make(__('event-matches.event'))
                ->title(fn (EventMatch $row) => $row->event->name)
                ->location(fn (EventMatch $row) => route('events.show', $row->event)),
            Column::make(__('event-matches.match_number'), 'match_number')
                ->searchable(),
            Column::make(__('event-matches.match_type'), 'matchType.name')
                ->searchable(),
            Column::make(__('event-matches.competitors'))
                ->label(fn (EventMatch $row) => $row->competitors->map(fn ($competitor) => $competitor->getCompetitor()->name)->join(' vs ')),
            Column::make(__('event-matches.result'))
                ->label(function (EventMatch $row): string {
                    $winner = $row->result?->winner;

                    if ($winner) {
                        return $winner->name.' by '.$row->result?->decision->name;
                    }

                    return 'N/A';
                }),
        ];
    }

    public function delete(EventMatch $eventMatch): void
    {
        $this->deleteModel($eventMatch);
    }
}
