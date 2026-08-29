<?php

declare(strict_types=1);

namespace App\Livewire\Venues\Tables;

use App\Builders\Events\EventBuilder;
use App\Livewire\Concerns\ShowTableTrait;
use App\Livewire\Table\Column;
use App\Livewire\Table\Columns\DateColumn;
use App\Livewire\Table\Columns\LinkColumn;
use App\Livewire\Table\DataTableComponent;
use App\Models\Events\Event;
use Livewire\Attributes\Locked;

/** @extends DataTableComponent<Event> */
class PreviousEvents extends DataTableComponent
{
    use ShowTableTrait;

    #[Locked]
    public ?int $venueId;

    protected string $databaseTableName = 'events';

    protected string $resourceName = 'events';

    /**
     * @return EventBuilder<Event>
     */
    public function builder(): EventBuilder
    {
        $venueId = $this->requireContextId($this->venueId ?? null, 'venue');

        return Event::query()
            ->forVenueId($venueId)
            ->latestDatedFirst();
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            LinkColumn::make(__('events.name'), 'name')
                ->title(fn (Event $row) => $row->name)
                ->location(fn (Event $row): string => route('events.show', $row)),
            DateColumn::make(__('events.date'), 'date')
                ->outputFormat('Y-m-d'),
        ];
    }
}
