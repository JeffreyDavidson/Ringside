<?php

declare(strict_types=1);

namespace App\Livewire\Events\Tables;

use App\Actions\Events\DeleteAction;
use App\Actions\Events\RestoreAction;
use App\Builders\Events\EventBuilder;
use App\Enums\EventStatus;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Table\Column;
use App\Livewire\Table\Columns\DateColumn;
use App\Livewire\Table\Columns\LinkColumn;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\DateRangeFilter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class Main extends BaseTable
{
    protected bool $showActionColumn = true;

    protected string $databaseTableName = 'events';

    protected string $routeBasePath = 'events';

    protected string $resourceName = 'events';

    /**
     * @return EventBuilder<Event>
     */
    public function builder(): EventBuilder
    {
        return Event::query()
            ->latestDatedFirst()
            ->with(['venue']);
    }

    public function configure(): void
    {
        Gate::authorize('viewList', Event::class);

        $this->addAdditionalSelects([
            'events.venue_id',
        ]);
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make(__('events.name'), 'name')
                ->searchable(),
            Column::make(__('core.status'), 'status')
                ->label(fn (Event $row) => $row->status->label())
                ->excludeFromColumnSelect(),
            DateColumn::make(__('events.date'), 'date')
                ->inputFormat('Y-m-d H:i:s')
                ->outputFormat('Y-m-d')
                ->emptyValue('No Date Set'),
            LinkColumn::make(__('events.venue'))
                ->title(fn (Event $row) => $row->venue ? $row->venue->name : 'No Venue')
                ->location(fn (Event $row) => $row->venue ? route('venues.show', $row->venue) : ''),

        ];
    }

    /**
     * @return array<int, Filter>
     */
    public function filters(): array
    {
        $venues = Venue::query()
            ->alphabetical()
            ->pluck('name', 'id')
            ->toArray();

        $statusOptions = ['' => __('core.all')];

        foreach (EventStatus::cases() as $status) {
            $statusOptions[$status->value] = $status->label();
        }

        return [
            SelectFilter::make(__('core.status'))
                ->setFilterPillTitle(__('core.status'))
                ->options($statusOptions)
                ->filter(function (EventBuilder $builder, string $value): void {
                    /** @var EventBuilder<Event> $builder */
                    match (EventStatus::tryFrom($value)) {
                        EventStatus::Scheduled => $builder->scheduled(),
                        EventStatus::Past => $builder->past(),
                        EventStatus::Unscheduled => $builder->unscheduled(),
                        default => null,
                    };
                }),
            DateRangeFilter::make('Event Dates')
                ->config([
                    'allowInput' => true,   // Allow manual input of dates
                    'altFormat' => 'F j, Y', // Date format that will be displayed once selected
                    'ariaDateFormat' => 'F j, Y', // An aria-friendly date format
                    'dateFormat' => 'Y-m-d', // Date format that will be received by the filter
                    'placeholder' => 'Enter Date Range', // A placeholder value
                    'locale' => 'en',
                ])
                ->setFilterPillValues([0 => 'minDate', 1 => 'maxDate']) // The values that will be displayed for the Min/Max Date Values
                ->filter(function (Builder $builder, array $dateRange): void { // Expects an array.
                    $builder
                        ->whereBetween('date', [$dateRange['minDate'], $dateRange['maxDate']]);
                }),
            SelectFilter::make('Venue')
                ->options([
                    '' => 'All',
                    ...$venues,
                ]),
        ];
    }

    public function delete(Event $event): void
    {
        Gate::authorize('delete', $event);

        resolve(DeleteAction::class)->handle($event);
        session()->flash('status', 'Event successfully deleted.');
    }

    /**
     * Restore a deleted scheduled event.
     */
    public function restore(int $eventId): void
    {
        $event = Event::onlyTrashed()->findOrFail($eventId);

        Gate::authorize('restore', $event);

        resolve(RestoreAction::class)->handle($event);
        session()->flash('status', 'Event successfully restored.');
        $this->redirect(route('events.index'));
    }
}
