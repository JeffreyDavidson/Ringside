<?php

declare(strict_types=1);

namespace App\Livewire\Events\Tables;

use App\Actions\Events\DeleteAction;
use App\Builders\Events\EventBuilder;
use App\Builders\Events\VenueBuilder;
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
use App\Models\Events\Venue;
use App\Models\Promotions\Promotion;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;

/**
 * @property-read array<int|string, string|null> $getVenues
 *
 * @extends BaseTable<Event>
 */
class Main extends BaseTable
{
    use ExecutesBusinessActions;
    use PresentsVenuesList;

    #[\Override]
    protected bool $showActionColumn = true;

    #[\Override]
    protected string $databaseTableName = 'events';

    #[\Override]
    protected string $routeBasePath = 'events';

    #[\Override]
    protected string $resourceName = 'events';

    /**
     * @return EventBuilder<Event>
     */
    public function builder(): EventBuilder
    {
        return Event::query()
            ->latestDatedFirst()
            ->orderBy('events.id')
            ->with(['venue', 'promotion']);
    }

    /**
     * The filter only offers venues this promotion's events use: venues are shared across promotions, so listing all
     * of them would grow with every tenant.
     *
     * @return VenueBuilder<Venue>
     */
    protected function venuesQuery(): VenueBuilder
    {
        return Venue::query()->whereIn('id', Event::query()->select('venue_id'));
    }

    protected function configure(): void
    {
        Gate::authorize('viewAny', Event::class);

        $this->addAdditionalSelects([
            'events.venue_id',
        ]);
    }

    #[\Override]
    public function render(): View
    {
        return view('livewire.events.tables.main', [
            'rows' => $this->getRows(),
            'perPageOptions' => $this->perPageAccepted,
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

    protected function getDefaultActionColumn(): Column
    {
        return Column::make(__('core.actions'))
            ->label(fn (Event $row) => view('components.tables.columns.event-actions', [
                'event' => $row,
            ])->render())
            ->html()
            ->excludeFromColumnSelect();
    }

    /**
     * @return array<int, Filter>
     */
    #[\Override]
    public function filters(): array
    {
        return [
            SelectFilter::make(__('core.status'), 'status')
                ->options(EventStatus::filterOptions())
                ->filter(function (EventBuilder $builder, string $value): void {
                    $status = EventStatus::tryFrom($value);

                    if ($status !== null) {
                        $builder->whereStatus($status);
                    }
                }),
            DateRangeFilter::make(__('core.event_dates'), 'event_dates')
                ->config([
                    'allowInput' => true,   // Allow manual input of dates
                    'altFormat' => 'F j, Y', // Date format that will be displayed once selected
                    'ariaDateFormat' => 'F j, Y', // An aria-friendly date format
                    'dateFormat' => 'Y-m-d', // Date format that will be received by the filter
                    'placeholder' => __('core.enter_date_range'), // A placeholder value
                    'locale' => 'en',
                ])
                ->setFilterPillValues([0 => 'minDate', 1 => 'maxDate']) // The values that will be displayed for the Min/Max Date Values
                ->filter(function (EventBuilder $builder, array $dateRange): void {
                    /** @var array{minDate: string, maxDate: string} $dateRange */
                    if (! Date::hasFormat($dateRange['minDate'], 'Y-m-d') || ! Date::hasFormat($dateRange['maxDate'], 'Y-m-d')) {
                        return;
                    }

                    $context = app(PromotionContextService::class);
                    $promotion = $context->isEnforced() ? $context->current() : null;

                    $builder->whereBetween('date', [
                        Promotion::parseLocalTime($promotion, "{$dateRange['minDate']} 00:00:00"),
                        Promotion::parseLocalTime($promotion, "{$dateRange['maxDate']} 23:59:59.999999"),
                    ]);
                }),
            SelectFilter::make(__('core.venue'), 'venue')
                ->options([
                    '' => 'All',
                    ...array_map(
                        static fn (?string $name): string => $name ?? '',
                        $this->getVenues,
                    ),
                ])
                ->filter(function (EventBuilder $builder, string $value): void {
                    $builder->forVenueId((int) $value);
                }),
        ];
    }

    public function delete(Event $event, DeleteAction $deleteAction): void
    {
        Gate::authorize('delete', $event);

        $this->executeBusinessAction(function () use ($deleteAction, $event): void {
            $deleteAction->handle($event);
        }, __('events.actions.deleted'));

        $this->forgetMetadata();
    }
}
