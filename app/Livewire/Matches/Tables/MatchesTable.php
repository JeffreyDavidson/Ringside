<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Tables;

use App\Actions\Matches\DeleteAction;
use App\Builders\Matches\EventMatchBuilder;
use App\Livewire\Concerns\ExecutesBusinessActions;
use App\Livewire\Concerns\ShowTableTrait;
use App\Livewire\Matches\Support\MatchTableFormatter;
use App\Livewire\Table\Column;
use App\Livewire\Table\Columns\ArrayColumn;
use App\Livewire\Table\DataTableComponent;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;

/** @extends DataTableComponent<EventMatch> */
class MatchesTable extends DataTableComponent
{
    use ExecutesBusinessActions;
    use ShowTableTrait;

    protected MatchTableFormatter $matchTableFormatter;

    /**
     * @var array<string, true>
     */
    protected array $unbookable = [];

    protected string $databaseTableName = 'events_matches';

    #[\Override]
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
            ->inCardOrder()
            ->withDisplayRelations();
    }

    protected function configure(): void
    {
        $this->authorizeContextRecord(Event::class, $this->eventId, 'event');

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
                ->label(fn (EventMatch $row): string => $this->matchTableFormatter->competitorLinks($row, $this->unbookable))
                ->html(),
            ArrayColumn::make(__('matches.referees'))
                ->data(fn (EventMatch $row) => $row->referees)
                ->outputFormat(fn (Referee $value): string => $this->matchTableFormatter->refereeLink(
                    $value,
                    isset($this->unbookable[MatchTableFormatter::unbookableKey($value)]),
                ))
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

    /**
     * Mark booked members of upcoming or unresulted matches who can no longer be booked, using one query per type.
     *
     * @param  Collection<int, EventMatch>  $rows
     */
    #[\Override]
    protected function projectRowState(Collection $rows): void
    {
        $booked = $rows->toBase()
            ->filter(fn (EventMatch $match): bool => $match->match_finish === null || $match->event->date >= now())
            ->flatMap(fn (EventMatch $match): Collection => $match->competitors->toBase()
                ->map(fn (MatchCompetitor $competitor): Wrestler|TagTeam => $competitor->competitor)
                ->merge($match->referees->toBase()))
            ->unique(MatchTableFormatter::unbookableKey(...));

        $bookable = $this->bookableKeys(Wrestler::class, $booked)
            ->merge($this->bookableKeys(TagTeam::class, $booked))
            ->merge($this->bookableKeys(Referee::class, $booked));

        $this->unbookable = array_fill_keys(
            $booked
                ->map(MatchTableFormatter::unbookableKey(...))
                ->reject(fn (string $key): bool => $bookable->contains($key))
                ->all(),
            true,
        );
    }

    /**
     * @param  class-string<Wrestler|TagTeam|Referee>  $type
     * @param  Collection<int, Wrestler|TagTeam|Referee>  $booked
     * @return Collection<int, string>
     */
    private function bookableKeys(string $type, Collection $booked): Collection
    {
        $members = $booked->filter(fn (Model $member): bool => $member instanceof $type);

        if ($members->isEmpty()) {
            return collect();
        }

        return $type::query()
            ->whereKey($members->map->getKey())
            ->bookable()
            ->get()
            ->toBase()
            ->map(MatchTableFormatter::unbookableKey(...));
    }

    public function delete(EventMatch $eventMatch, DeleteAction $deleteAction): void
    {
        Gate::authorize('delete', $eventMatch);

        $this->executeBusinessAction(function () use ($deleteAction, $eventMatch): void {
            $deleteAction->handle($eventMatch);
        }, __('matches.actions.deleted'));

        $this->forgetMetadata();
    }
}
