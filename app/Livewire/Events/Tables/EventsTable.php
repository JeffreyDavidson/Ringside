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
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class EventsTable extends BaseTableWithActions
{
    use HasStatusColumn, HasStatusFilter;

    protected string $databaseTableName = 'events';

    protected string $routeBasePath = 'events';

    protected string $resourceName = 'events';

    public function builder(): EventBuilder
    {
        return Event::query()
            ->oldest('name')
            ->when($this->getAppliedFilterWithValue('Status'), fn ($query, $status) => $query->where('status', $status))
            ->when($this->getAppliedFilterWithValue('Venue'), fn ($query, $venue) => $query->where('venue', $venue));
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
            Column::make(__('events.name'), 'name')
                ->searchable(),
            $this->getDefaultStatusColumn(),
            DateColumn::make(__('events.date'), 'date')
                ->outputFormat('Y-m-d'),
            Column::make(__('venues.name'), 'venue.name'),
        ];
    }

    /**
     * Undocumented function
     *
     * @return array<int, Filter>
     */
    public function filters(): array
    {
        $statuses = collect(EventStatus::cases())->pluck('name', 'value')->toArray();
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
                ->filter(function (Builder $builder, array $dateRange) { // Expects an array.
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
