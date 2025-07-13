<?php

declare(strict_types=1);

namespace App\Livewire\Events\Tables;

use App\Actions\Events\RestoreAction;
use App\Builders\Events\EventBuilder;
use App\Livewire\Base\Tables\BaseTableWithActions;
use App\Models\Events\Event;
use App\Models\Shared\Venue;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class EventsTable extends BaseTableWithActions
{
    protected string $databaseTableName = 'events';

    protected string $routeBasePath = 'events';

    protected string $resourceName = 'events';

    /**
     * @return EventBuilder<Event>
     */
    public function builder(): EventBuilder
    {
        return Event::query()
            ->with(['venue'])
            ->orderBy(DB::raw('date IS NOT NULL, date'), 'desc');
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
                ->label(fn ($row) => $row->status?->label() ?? 'Unknown')
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
        /** @var array<string, string> $statuses */
        $statuses = [
            'scheduled' => 'Scheduled',
            'unscheduled' => 'Unscheduled',
            'past' => 'Past',
            'future' => 'Future',
        ];

        /** @var array<int, Venue> $venues */
        $venues = Venue::query()->orderBy('name')->pluck('name', 'id')->toArray();

        return [
            SelectFilter::make(__('core.status')) // @phpstan-ignore-line method.notFound
                ->setFilterPillTitle(__('core.status'))
                ->options([
                    '' => __('core.all'),
                    'schedule' => 'Scheduled',
                    'past' => 'Past',
                    'unscheduled' => 'Unscheduled',
                ])
                ->filter(function ($builder, string $value) {
                    /** @var EventBuilder<Event> $builder */
                    match ($value) {
                        'scheduled' => $builder->scheduled(),
                        'past' => $builder->past(),
                        'unscheduled' => $builder->unscheduled(),
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
        $this->deleteModel($event);
    }

    /**
     * Restore a deleted scheduled event.
     */
    public function restore(int $eventId): RedirectResponse
    {
        $event = Event::onlyTrashed()->findOrFail($eventId);

        Gate::authorize('restore', $event);

        try {
            resolve(RestoreAction::class)->handle($event);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }
}
