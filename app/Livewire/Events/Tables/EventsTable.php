<?php

declare(strict_types=1);

namespace App\Livewire\Events\Tables;

use App\Builders\EventBuilder;
use App\Enums\EventStatus;
use App\Livewire\Base\Tables\BaseTableWithActions;
use App\Livewire\Concerns\Columns\HasStatusColumn;
use App\Livewire\Concerns\Filters\HasStatusFilter;
use App\Models\Event;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

final class EventsTable extends BaseTableWithActions
{
    use HasStatusColumn, HasStatusFilter;

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
            $this->getDefaultStatusColumn(),
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
        $statuses = collect(EventStatus::cases())->pluck('name', 'value')->toArray();

        /** @var array<int, Venue> $venues */
        $venues = Venue::query()->orderBy('name')->pluck('name', 'id')->toArray();

        return [
            $this->getDefaultStatusFilter($statuses),
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
}
