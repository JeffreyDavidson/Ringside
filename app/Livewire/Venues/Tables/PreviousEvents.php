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
use Exception;

class PreviousEvents extends DataTableComponent
{
    use ShowTableTrait;

    public ?int $venueId;

    protected string $databaseTableName = 'events';

    protected string $resourceName = 'events';

    /**
     * @return EventBuilder<Event>
     */
    public function builder(): EventBuilder
    {
        if (! isset($this->venueId)) {
            throw new Exception("You didn't specify a venue");
        }

        return Event::query()
            ->where('venue_id', $this->venueId)
            ->orderByDesc('date');
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
            LinkColumn::make(__('events.name'), 'name')
                ->title(fn (Event $row) => $row->name)
                ->location(fn (Event $row) => route('events.show', $row)),
            DateColumn::make(__('events.date'), 'date')
                ->outputFormat('Y-m-d'),
        ];
    }
}
