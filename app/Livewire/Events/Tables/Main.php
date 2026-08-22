<?php

declare(strict_types=1);

namespace App\Livewire\Events\Tables;

use App\Actions\Events\DeleteAction;
use App\Actions\Events\RestoreAction;
use App\Builders\Events\EventBuilder;
use App\Enums\EventStatus;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Concerns\Data\PresentsVenuesList;
use App\Livewire\Concerns\ExecutesBusinessActions;
use App\Livewire\Table\Column;
use App\Livewire\Table\Columns\DateColumn;
use App\Livewire\Table\Columns\LinkColumn;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\DateRangeFilter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Events\Event;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;

/** @extends BaseTable<Event> */
class Main extends BaseTable
{
    use ExecutesBusinessActions;
    use PresentsVenuesList;

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

    protected function configure(): void
    {
        Gate::authorize('viewAny', Event::class);

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
                ->location(fn (Event $row): string => $row->venue ? route('venues.show', $row->venue) : ''),

        ];
    }

    /**
     * @return array<int, Filter>
     */
    public function filters(): array
    {
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
                ->filter(function (EventBuilder $builder, array $dateRange): void {
                    /** @var array{minDate: string, maxDate: string} $dateRange */
                    $startDate = Date::createFromFormat('Y-m-d', $dateRange['minDate']);
                    $endDate = Date::createFromFormat('Y-m-d', $dateRange['maxDate']);

                    if ($startDate === null || $endDate === null) {
                        return;
                    }

                    $builder->whereBetween('date', [
                        $startDate->startOfDay(),
                        $endDate->endOfDay(),
                    ]);
                }),
            SelectFilter::make('Venue')
                ->options([
                    '' => 'All',
                    ...array_map(
                        static fn (?string $name): string => $name ?? '',
                        $this->getVenues(),
                    ),
                ]),
        ];
    }

    public function delete(Event $event): void
    {
        Gate::authorize('delete', $event);

        $this->executeBusinessAction(function () use ($event): void {
            resolve(DeleteAction::class)->handle($event);
        }, __('events.actions.deleted'));
    }

    /**
     * Restore a deleted scheduled event.
     */
    public function restore(int $eventId): void
    {
        $event = Event::onlyTrashed()->findOrFail($eventId);

        Gate::authorize('restore', $event);

        if ($this->executeBusinessAction(function () use ($event): void {
            resolve(RestoreAction::class)->handle($event);
        }, __('events.actions.restored'))) {
            $this->redirectRoute('events.index');
        }
    }
}
