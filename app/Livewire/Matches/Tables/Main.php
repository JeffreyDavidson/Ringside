<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Tables;

use App\Actions\Matches\DeleteAction;
use App\Builders\Matches\EventMatchBuilder;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Table\Column;
use App\Livewire\Table\Columns\LinkColumn;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use Illuminate\Support\Facades\Gate;

/** @extends BaseTable<EventMatch> */
class Main extends BaseTable
{
    protected bool $showActionColumn = false;

    protected string $databaseTableName = 'events_matches';

    protected string $routeBasePath = 'matches';

    protected string $resourceName = 'matches';

    /**
     * @return EventMatchBuilder<EventMatch>
     */
    public function builder(): EventMatchBuilder
    {
        return EventMatch::query()
            ->latestEventFirst()
            ->with(['event', 'competitors.competitor', 'winningSide.competitors.competitor']);
    }

    public function configure(): void
    {
        Gate::authorize('viewList', EventMatch::class);

        $this->addAdditionalSelects([
            'events_matches.event_id',
            'events_matches.match_type',
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
            Column::make(__('event-matches.match_type'), 'match_type')
                ->label(fn (EventMatch $row) => $row->match_type->label())
                ->searchable(),
            Column::make(__('event-matches.competitors'))
                ->label(fn (EventMatch $row) => $row->competitors->map(fn (MatchCompetitor $competitor) => $competitor->competitor->name)->join(' vs ')),
            Column::make(__('event-matches.result'))
                ->label(function (EventMatch $row): string {
                    if ($row->match_finish === null) {
                        return 'N/A';
                    }

                    if ($row->winningSide) {
                        $winners = $row->winningSide->competitors
                            ->map(fn (MatchCompetitor $competitor): string => $competitor->competitor->name)
                            ->join(' & ');

                        return $winners.' by '.$row->match_finish->label();
                    }

                    return $row->match_finish->label();
                }),
        ];
    }

    public function delete(EventMatch $eventMatch): void
    {
        Gate::authorize('delete', $eventMatch);

        resolve(DeleteAction::class)->handle($eventMatch);

        session()->flash('status', 'Match successfully deleted.');
    }
}
